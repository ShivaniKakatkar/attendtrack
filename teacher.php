<?php require 'lib.php';
$err = '';
$mode = $_POST['mode'] ?? 'login';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check();
  $roll = trim($_POST['name'] ?? '');
  $pw = $_POST['password'] ?? '';
  if ($mode === 'register') {
    $name = $roll;
    if (!$roll || !$name || strlen($pw) < 6) $err = 'Fill all fields (password min 6 chars).';
    else {
      $s = db()->prepare('SELECT 1 FROM teachers WHERE name=?');
      $s->execute([$roll]);
      if ($s->fetch()) $err = 'Name already registered.';
      else {
        db()->prepare('INSERT INTO teachers(name,password) VALUES(?,?)')->execute([$name, password_hash($pw, PASSWORD_DEFAULT)]);
        $_SESSION['teacher'] = (int)db()->lastInsertId();
        $_SESSION['tname'] = $name;
        header('Location: teacher_dashboard.php');
        exit;
      }
    }
  } else {
    $s = db()->prepare('SELECT * FROM teachers WHERE name=?');
    $s->execute([$roll]);
    $u = $s->fetch();
    if ($u && password_verify($pw, $u['password'])) {
      session_regenerate_id(true);
      $_SESSION['teacher'] = (int)$u['id'];
      $_SESSION['tname'] = $u['name'];
      header('Location: teacher_dashboard.php');
      exit;
    }
    $err = 'Invalid name or password.';
  }
}
head('Teacher Login'); ?>
<div class="card narrow">
  <h2>Teacher</h2>
  <div class="tabs"><a href="#" id="tl" class="on">Sign in</a><a href="#" id="tr">Sign up</a></div>
  <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
  <form method="post" id="f"><?= csrf() ?><input type="hidden" name="mode" id="mode" value="login">
    <label>Name<input name="name" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn" id="go">Sign in</button>
  </form>
</div>
<script>
  const r = (on) => {
    mode.value = on ? 'register' : 'login';
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
