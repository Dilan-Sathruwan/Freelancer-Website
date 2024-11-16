<?php
include_once "../../Freelancer-Website/config/db.php";

if (isset($_POST["submit"])) {
  if (!empty($_POST["username"]) && !empty($_POST["password"])) {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    try {
      $sql = "SELECT * FROM users WHERE username = :username";

      $stmt = $conn->prepare($sql);
      $stmt->bindParam(":username", $username, PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($password == $row["password"]) {
          session_start();
          $_SESSION["id"] = $row["id"];
          $_SESSION["username"] = $row["username"];
          $_SESSION["role"] = $row["role"];

          if ($row["role"] == "freelancer") {
            header("Location: ../freelancer/index.php");
            exit();
          } else if ($row["role"] == "client") {
            header("Location: ../client/index.php");
            exit();
          } else if ($row["role"] == "admin") {
            header("Location: ../admin/index.php");
            exit();
          }
          exit();
        } else {
          header("Location: login.php?error=Invalid username or password.");
          exit();
        }
      } else {
        header("Location: login.php?error=Invalid username or password.");
        exit();
      }

    } catch (PDOException $e) {
      echo "Database error: " . $e->getMessage();
    }
  } else {
    header("Location: login.php?error=Please fill in all required fields.");
    exit();
  }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page</title>

  <!-- link external css  -->
  <link rel="stylesheet" href="../assets/css/reset.css">
  <link rel="stylesheet" href="../assets/css/login.css" />

  <!-- link bootstrap cdn -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>

<body>

  <button class="backToHome"><a href="../index.php" class="text-white">back to home</a></button>
  <div class="border-red form">
    <form action="./login.php" method="POST">

      <h1 class="text-center ">Login</h2>
        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required />
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>
        <button type="submit" name="submit">Login</button>
        <div class="forgot-password">
          <a href="#">Forgot Password?</a>
        </div>
    </form>
  </div>
</body>

</html>