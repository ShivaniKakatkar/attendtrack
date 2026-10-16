<?php require 'lib.php';
$err = '';
$mode = $_POST['mode'] ?? 'login';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check();
  $roll = trim($_POST['roll'] ?? '');
  $pw = $_POST['password'] ?? '';
  if ($mode === 'register') {
    $name = trim($_POST['name'] ?? '');
    if (!$roll || !$name || strlen($pw) < 6) $err = 'Fill all fields (password min 6 chars).';
    else {
      $s = db()->prepare('SELECT 1 FROM students WHERE roll_no=?');
      $s->execute([$roll]);
      if ($s->fetch()) $err = 'Roll number already registered.';
      else {
        db()->prepare('INSERT INTO students VALUES(?,?,?)')->execute([$roll, $name, password_hash($pw, PASSWORD_DEFAULT)]);
        $_SESSION['student'] = $roll;
        $_SESSION['sname'] = $name;
        header('Location: student_dashboard.php');
        exit;
      }
    }
  } else {
    $s = db()->prepare('SELECT * FROM students WHERE roll_no=?');
    $s->execute([$roll]);
    $u = $s->fetch();
    if ($u && password_verify($pw, $u['password'])) {
      session_regenerate_id(true);
      $_SESSION['student'] = $u['roll_no'];
      $_SESSION['sname'] = $u['name'];
      header('Location: student_dashboard.php');
      exit;
    }
    $err = 'Invalid roll number or password.';
  }
}
head('Student Login'); ?>
<div class="card narrow">
  <h2>Student</h2>
  <div class="tabs"><a href="#" id="tl" class="on">Sign in</a><a href="#" id="tr">Sign up</a></div>
  <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
  <form method="post" id="f"><?= csrf() ?><input type="hidden" name="mode" id="mode" value="login">
    <label>Roll number<input name="roll" required></label>
    <label id="nm" hidden>Full name<input name="name"></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn" id="go">Sign in</button>
  </form>
</div>
<script>
  const r = (on) => {
    mode.value = on ? 'register' : 'login';
    nm.hidden = !on;
    go.textContent = on ? 'Create account' : 'Sign in';
    tr.className = on ? 'on' : '';
    tl.className = on ? '' : 'on'
  };
  tl.onclick = e => {
    e.preventDefault();
    r(false)
  };
  tr.onclick = e => {
    e.preventDefault();
    r(true)
  };
  <?php if ($mode === 'register') echo 'r(true);'; ?>
</script><?php foot();
