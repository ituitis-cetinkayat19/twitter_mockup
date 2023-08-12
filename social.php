<?php
    session_start();
    include "include/header.php";

    if(isset($_SESSION["username"])) 
    {
        $sql = "SELECT username FROM users WHERE username <> ? AND username NOT IN
        (SELECT friend1 FROM friends WHERE friend2 = ? UNION SELECT friend2 FROM friends WHERE friend1 = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $_SESSION["username"], $_SESSION["username"], $_SESSION["username"]);
        $stmt->execute();
        $newPeople = $stmt->get_result();
        $stmt->close();
    }

    if($_SERVER["REQUEST_METHOD"] == "POST") 
    {
        if(isset($_POST["logout"])) 
        {
            session_unset();
            session_destroy();
            header("Location: login.php");
        }
        elseif(isset($_POST["send"]))
        {
            $sql = "SELECT to_user, from_user FROM friend_requests WHERE to_user = ? AND from_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $_POST["to_user"], $_SESSION["username"]);
            $stmt->execute();
            $alreadySent = $stmt->get_result();

            if($alreadySent->num_rows !== 1)
            {
                $sql = "INSERT INTO friend_requests (to_user, from_user) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $_POST["to_user"], $_SESSION["username"]);
                $stmt->execute();
            }
        }
    }


?>

<!DOCTYPE html>
<body>
    <?php if(isset($_SESSION["username"])): ?>
        <h3>People You Haven't Met</h3>
        <?php while($row = mysqli_fetch_assoc($newPeople)): ?>
            <?php echo "$row[username]" ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                <input type="hidden" name="to_user" value="<?php echo $row["username"] ?>">
                <button name="send" type="submit">Send Request</button><br><br>
            </form>
        <?php endwhile ?>
        <a href="dashboard.php">Go to Dashboard Page</a><br><br>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
            <button name="logout" type="submit">Logout</button>
        </form>
    <?php else: ?>
        <p>You are not logged in!</p><br><br>
    <?php endif ?>
<?php include "include/footer.php" ?>