<?php require 'lib.php';
need('student');
$roll = $_SESSION['student'];
$tab = $_GET['tab'] ?? 'attendance';
head('Dashboard', $_SESSION['sname'] . " ($roll)"); ?>
<div class="tabs big"><a href="?tab=attendance" class="<?= $tab === 'attendance' ? 'on' : '' ?>">Attendance</a><a href="?tab=assignments" class="<?= $tab === 'assignments' ? 'on' : '' ?>">Assignments</a></div>
<?php if ($tab === 'attendance'):
  $s = db()->prepare('SELECT subject,attended,total FROM attendance_summary WHERE roll_no=?');
  $s->execute([$roll]);
  $rows = $s->fetchAll();
  $pos = function ($x) {
    $i = array_search($x['subject'], SUBJECTS);
    return $i === false ? 99 : $i;
  };
  usort($rows, fn($a, $b) => $pos($a) <=> $pos($b));
  if (!$rows) echo '<div class="card">No attendance uploaded for your roll number yet.</div>';
  else {
    $A = array_sum(array_column($rows, 'attended'));
    $T = array_sum(array_column($rows, 'total')); ?>
    <div class="sheet">
      <table>
        <thead>
          <tr>
            <th>Subject</th>
            <th>Attended</th>
            <th>Total Classes</th>
            <th>%</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): $a = (int)$r['attended'];
            $t = (int)$r['total'];
            $pc = $t ? round($a * 100 / $t, 2) : 0;
            $low = $pc < 75; ?>
            <tr>
              <th><?= e($r['subject']) ?></th>
              <td><?= $a ?></td>
              <td><?= $t ?></td>
              <td class="<?= $low ? 'a' : 'p' ?>"><b><?= $pc ?>%</b></td>
              <td><?= $low ? 'Attend ' . max(0, 3 * $t - 4 * $a) . ' more to reach 75%' : 'OK' ?></td>
            </tr>
          <?php endforeach;
          $oc = $T ? round($A * 100 / $T, 2) : 0; ?>
          <tr>
            <th>Overall</th>
            <td><b><?= $A ?></b></td>
            <td><b><?= $T ?></b></td>
            <td class="<?= $oc < 75 ? 'a' : 'p' ?>"><b><?= $oc ?>%</b></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="muted">Below 75% is highlighted red. "Attend N more" assumes you attend every upcoming class.</p>
  <?php }
else:
  $sel = $_GET['subject'] ?? ''; ?>
  <div class="pills"><a href="?tab=assignments" class="<?= $sel ? '' : 'on' ?>">All</a><?php foreach (SUBJECTS as $s): ?><a href="?tab=assignments&subject=<?= urlencode($s) ?>" class="<?= $sel === $s ? 'on' : '' ?>"><?= e($s) ?></a><?php endforeach; ?></div>
  <?php $q = db()->prepare('SELECT a.*,t.name tn FROM assignments a JOIN teachers t ON t.id=a.teacher_id WHERE (?="" OR subject=?) ORDER BY created_at DESC');
  $q->execute([$sel, $sel]);
  $rows = $q->fetchAll();
  if (!$rows) echo '<div class="card">No assignments yet.</div>';
  foreach ($rows as $a): ?>
    <div class="card row">
      <div><span class="tag"><?= e($a['subject']) ?></span>
        <h3><?= e($a['title']) ?></h3><small>By <?= e($a['tn']) ?> · <?= e(date('d M Y', strtotime($a['created_at']))) ?></small>
      </div><a class="btn" href="uploads/<?= e($a['file']) ?>" download="<?= e($a['orig_name']) ?>">Download</a>
    </div>
<?php endforeach;
endif;
foot();
