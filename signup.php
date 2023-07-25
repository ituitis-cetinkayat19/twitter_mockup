<?php
  session_start();
  include "include/header.php";

  $username = $email = $password = "";
  $usernameErr = $emailErr = $passwordErr = "";
  if($_SERVER["REQUEST_METHOD"] == "POST") 
  {
    if(empty($_POST["username"]))
      $usernameErr = "Username is required!";
    else
      $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if(empty($_POST["email"]))
      $emailErr = "Email is required!";
    else 
    {
      $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
      if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        $emailErr = "Invalid email address!";
    }

    if(empty($_POST["password"]))
      $passwordErr = "Password is required!";
    elseif($_POST["password"] !== $_POST["confirm"])
      $passwordErr = "Passwords do not match!";

    $hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

    if(empty($nameErr) && empty($emailErr) && empty($passwordErr)) 
    {
      $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $username, $hash, $email);

      try
      {
        $stmt->execute();
        $_SESSION["username"] = $username;
        header("Location: dashboard.php");
      }
      catch(Exception $e)
      {
        echo "User with that name already exists!";
      }
      finally
      {
        $stmt->close();
      }
    }
  }
?>

<!DOCTYPE html>
<body>
  <h2>Sign Up</h2>
  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>" method="post">
    <label>Username:</label><br>
    <input type="text" name="username"><?php echo " $usernameErr" ?><br><br>
    <label>Password:</label><br>
    <input type="password" name="password"><?php echo " $passwordErr" ?><br><br>
    <label>Confirm Password:</label><br>
    <input type="password" name="confirm"><br><br>
    <label>Email:</label><br>
    <input type="email" name="email"><?php echo " $emailErr" ?><br><br>
    <input type="submit" name="submit" value="Submit">
  </form>
  <br><br>
<?php include "include/footer.php" ?>