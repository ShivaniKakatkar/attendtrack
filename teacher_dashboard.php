<?php require 'lib.php';
need('teacher');

use PhpOffice\PhpSpreadsheet\IOFactory;

function parse_attendance(array $rows)
{
  $n = fn($v) => strtolower(trim(preg_replace('/\s+/', ' ', (string)$v)));
  $h = $rc = null;
  foreach (array_slice($rows, 0, 30, true) as $i => $r) foreach ($r as $c => $v) {
    $x = $n($v);
    if ($x !== '' && strlen($x) <= 25 && preg_match('/\b(roll|reg|registration|usn)\b/', $x)) {
      $h = $i;
      $rc = $c;
      break 2;
    }
  }
  if ($h === null) throw new RuntimeException('No "Roll No" column found in the first 30 rows.');
  $hdr = $rows[$h];
  $out = [];
  $sc = $tc = null;
  foreach ($hdr as $c => $v) {
    $x = $n($v);
    if ($sc === null && preg_match('/^(subject|course|paper)( name| code)?$/', $x)) $sc = $c;
    if ($tc === null && preg_match('/^(status|attendance|present|p\/a|a\/p)$/', $x)) $tc = $c;
  }
  if ($sc !== null && $tc !== null) {            
    foreach (array_slice($rows, $h + 1) as $r) {
      $roll = trim((string)($r[$rc] ?? ''));
      $sub = trim((string)($r[$sc] ?? ''));
      $st = strtoupper(substr(trim((string)($r[$tc] ?? '')), 0, 1));
      if ($roll === '' || $sub === '' || !in_array($st, ['P', 'A'])) continue;
      $out[$roll][$sub] ??= [0, 0];
      $out[$roll][$sub][1]++;
      if ($st === 'P') $out[$roll][$sub][0]++;
    }
  } else {                               
    $cols = [];
    foreach ($hdr as $c => $v) {
      if ($c === $rc || !preg_match('/^(.*?)\s*(?:classes\s+)?(?:attended|present)$/i', trim((string)$v), $m)) continue;
      $sub = trim($m[1]);
      $t = 0;
      for ($rr = $h - 1; $rr >= max(0, $h - 4) && $t === 0; $rr--) foreach ([$c, $c + 1] as $cc) {
        $x = $rows[$rr][$cc] ?? null;
        if ($t === 0) {
          if (is_numeric($x) && $x > 0) $t = (int)$x;
          elseif (is_string($x) && preg_match('/total\D*(\d+)/i', $x, $mm)) $t = (int)$mm[1];
        }
      }
      for ($rr = $h - 1; $rr >= max(0, $h - 4) && $sub === ''; $rr--) foreach ([$c, $c - 1] as $cc) {
        $x = $rows[$rr][$cc] ?? null;
        if (is_string($x) && trim($x) !== '' && !preg_match('/total|^\d+$/i', trim($x))) {
          $sub = trim($x);
          break;
        }
      }
      if ($sub !== '' && $t > 0) $cols[$c] = [$sub, $t];
    }
    foreach (array_slice($rows, $h + 1) as $r) {
      $roll = trim((string)($r[$rc] ?? ''));
      if ($roll === '' || preg_match('/^(total|average|avg)/i', $roll)) continue;
      foreach ($cols as $c => [$sub, $t]) $out[$roll][$sub] = [max(0, min($t, (int)($r[$c] ?? 0))), $t];
    }
  }
  if (!$out) throw new RuntimeException('Found a Roll No column but no attendance data. Need either Subject + Status (P/A) columns, or "<Subject> Attended" columns with total classes in a row above.');
  return $out;
}
$tab = $_GET['tab'] ?? 'attendance';
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check();
  $f = $_FILES['file'] ?? null;
  $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
  if (!$f || $f['error'] !== UPLOAD_ERR_OK) $err = 'Please choose a file.';
  elseif ($_POST['do'] === 'attendance') {
    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) $err = 'Upload an .xlsx, .xls or .csv file.';
    elseif (!is_file(__DIR__ . '/vendor/autoload.php')) $err = 'Run "composer install" first.';
    else {
      require __DIR__ . '/vendor/autoload.php';
      try {
        $wb = IOFactory::load($f['tmp_name']);
        $data = [];
        $last = 'Could not find attendance data.';
        foreach ($wb->getAllSheets() as $sh) {
          try {
            foreach (parse_attendance($sh->toArray(null, true, false, false)) as $roll => $subs) foreach ($subs as $s => $v) $data[$roll][$s] = $v;
          } catch (RuntimeException $x) {
            $last = $x->getMessage();
          }
        }
        if (!$data) throw new RuntimeException($last);
        $st = db()->prepare('INSERT INTO attendance_summary(roll_no,subject,attended,total) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE attended=VALUES(attended),total=VALUES(total)');
        $subjects = [];
        db()->beginTransaction();
        foreach ($data as $roll => $subs) foreach ($subs as $s => [$at, $tt]) {
          $st->execute([(string)$roll, $s, $at, $tt]);
          $subjects[$s] = 1;
        }
        db()->commit();
        $msg = 'Imported attendance for ' . count($data) . ' students across ' . count($subjects) . ' subjects.';
      } catch (Throwable $x) {
        if (db()->inTransaction()) db()->rollBack();
        $err = $x instanceof RuntimeException ? $x->getMessage() : 'Could not read that file.';
      }
    }
  } else {
    $sub = $_POST['subject'] ?? '';
    $title = trim($_POST['title'] ?? '');
    if (!in_array($sub, SUBJECTS) || !$title) $err = 'Select a subject and enter a title.';
    elseif (!in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'png', 'jpg'])) $err = 'File type not allowed.';
    elseif ($f['size'] > 10 * 1024 * 1024) $err = 'Max file size is 10 MB.';
    else {
      $name = bin2hex(random_bytes(10)) . ".$ext";
      move_uploaded_file($f['tmp_name'], __DIR__ . "/uploads/$name");
      db()->prepare('INSERT INTO assignments(subject,title,file,orig_name,teacher_id) VALUES(?,?,?,?,?)')->execute([$sub, $title, $name, basename($f['name']), $_SESSION['teacher']]);
      $msg = 'Assignment uploaded.';
    }
  }
}
head('Teacher Dashboard', $_SESSION['tname']); ?>
<div class="tabs big"><a href="?tab=attendance" class="<?= $tab === 'attendance' ? 'on' : '' ?>">Upload Attendance</a><a href="?tab=assignments" class="<?= $tab === 'assignments' ? 'on' : '' ?>">Upload Assignment</a></div>
<?php if ($msg) echo '<p class="ok">' . e($msg) . '</p>';
if ($err) echo '<p class="err">' . e($err) . '</p>'; ?>
<?php if ($tab === 'attendance'): ?>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf() ?><input type="hidden" name="do" value="attendance">
    <div id="drop" class="drop">
      <p><b>Drag &amp; drop</b> your Excel file here, or click to browse</p><small id="fn">.xlsx / .xls / .csv</small>
    </div>
    <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" hidden required>
     <p class="muted">Upload any attendance sheet.</p>
      <!--<p class="muted">Only <b>roll number, subject, classes attended and total classes</b> are saved; names, %, dates and other columns are ignored. Supported: (1) columns <b>Roll No | Subject | Status (P/A)</b>, one row per class, or (2) a <b>Roll No</b> column plus one <b>"&lt;Subject&gt; Attended"</b> column per subject with total classes in a row above. Every sheet in the workbook is checked. Re-uploading updates existing records.</p> -->
    <button class="btn">Upload attendance</button>
  </form>
  <script>
    const d = drop,
      i = file;
    d.onclick = () => i.click();
    i.onchange = () => fn.textContent = i.files[0]?.name || '';
    ['dragover', 'dragenter'].forEach(v => d.addEventListener(v, e => {
      e.preventDefault();
      d.classList.add('hot')
    }));
    ['dragleave', 'drop'].forEach(v => d.addEventListener(v, e => {
      e.preventDefault();
      d.classList.remove('hot')
    }));
    d.addEventListener('drop', e => {
      i.files = e.dataTransfer.files;
      i.onchange()
    });
  </script>
<?php else: ?>
  <form method="post" enctype="multipart/form-data" class="card"><?= csrf() ?><input type="hidden" name="do" value="assignment">
    <label>Subject<select name="subject" required>
        <option value="">Select subject</option><?php foreach (SUBJECTS as $s) echo '<option>' . e($s) . '</option>'; ?>
      </select></label>
    <label>Title<input name="title" required></label>
    <label>File (PDF, DOC, PPT, ZIP… max 10 MB)<input type="file" name="file" required></label>
    <button class="btn">Upload assignment</button>
  </form>
<?php $q = db()->prepare('SELECT * FROM assignments WHERE teacher_id=? ORDER BY created_at DESC');
  $q->execute([$_SESSION['teacher']]);
  foreach ($q as $a) echo '<div class="card row"><div><span class="tag">' . e($a['subject']) . '</span> ' . e($a['title']) . '</div><small>' . e($a['orig_name']) . '</small></div>';
endif;
foot();
