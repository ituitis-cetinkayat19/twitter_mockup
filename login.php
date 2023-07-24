<?php
  session_start();
  include "config/database.php";

  $username = $password = "";
  $usernameErr = $passwordErr = "";
  if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty($_POST["username"])) {
      $usernameErr = "Username is required!";
    } else {
      $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    if(empty($_POST["password"])) {
      $passwordErr = "Password is required!";
    }

    if(empty($nameErr) && empty($passwordErr)) {
      $sql = "SELECT username, password FROM users WHERE username = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $username);
      if(!$stmt->execute())
        echo "Error: " . mysqli_error($conn);

      $result = $stmt->get_result();

      if($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if(password_verify($_POST["password"], $row["password"])) {
          $_SESSION["username"] = $username;
          header("Location: dashboard.php");          
        } else {
          echo "wrong password";
        }
      } else {
        echo "wrong username";
      }
      $stmt->close();
    }
  }
?>

<!DOCTYPE html>
<head>
  <title>Login Page</title>
</head>
<body>
  <h2>Login</h2>
  <form method="post" action="<?php htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
    <label>Username:</label><br>
    <input type="text" name="username"><?php echo " $usernameErr" ?><br><br>
    <label>Password:</label><br>
    <input type="password" name="password"><?php echo " $passwordErr" ?><br><br>
    <input type="submit" name="submit" value="Submit">
  </form>
  <br><br>
  <a href="signup.php">Go to Signup Page</a>
</body>
</html>