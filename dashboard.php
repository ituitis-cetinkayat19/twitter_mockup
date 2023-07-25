<?php
    session_start();
    include "config/database.php";

    if(isset($_SESSION["username"])) 
    {
        $sql = "SELECT friend1, friend2 FROM Friends WHERE friend1 = ? OR friend2 = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $_SESSION["username"], $_SESSION["username"]);
        $stmt->execute();

        $friendships = $stmt->get_result();
        $friends = array();
        while($row = mysqli_fetch_assoc($friendships))
            $friends[] = $row["friend1"] == $_SESSION["username"] ? $row["friend2"] : $row["friend1"];

        if(count($friends) > 0) 
        {
            $sql = "SELECT context, owner, date FROM tweets WHERE owner = ? OR owner IN (";
            $friendPlaceholders = implode(',', array_fill(0, count($friends), '?'));
            $sql .= $friendPlaceholders . ") ORDER BY date";
            $stmt = $conn->prepare($sql);

            $bindParams = array_merge(array($_SESSION["username"]), $friends);
            $types = str_repeat('s', count($bindParams));

            $stmt->bind_param($types, ...$bindParams);
        }
        else
        {
            $sql = "SELECT context, owner, date FROM tweets WHERE owner = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $_SESSION["username"]);
        }
        $stmt->execute();
      
        $result = $stmt->get_result();  

        $sql = "SELECT from_user, date FROM friend_requests WHERE to_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $_SESSION["username"]);
        $stmt->execute();
  
        $requests = $stmt->get_result();

        $stmt->close();
    }

    $tweetErr = $tweet = "";
    if($_SERVER["REQUEST_METHOD"] == "POST") 
    {
        if(isset($_POST["logout"])) 
        {
            session_unset();
            session_destroy();
            header("Location: login.php");
        }
        elseif(isset($_POST["tweet"])) 
        {
            if(empty($_POST["context"]))
                $tweetErr = "Tweet is required!";
            else
                $tweet = filter_input(INPUT_POST, "context", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if(empty($tweetErr)) 
            {
                $sql = "INSERT INTO tweets(context, owner) VALUES(?, ?)";
                $stmt = $conn->prepare($sql);
                
                $stmt->bind_param("ss", $tweet, $_SESSION["username"]);
                if($stmt->execute()) 
                    header("Location: dashboard.php");
                else
                    echo "Error: " . mysqli_error($conn); 
            }
        }
        elseif(isset($_POST["accept"])) 
        {
            $sql = "INSERT INTO friends(friend1, friend2) VALUES(?,?)";
            $stmt = $conn->prepare($sql);
            print_r($_POST);
            $stmt->bind_param("ss", $_POST["from_user"] , $_SESSION["username"]);
            $stmt->execute();

            $sql = "DELETE FROM friend_requests WHERE from_user = ? AND to_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $_POST["from_user"] , $_SESSION["username"]);
            $stmt->execute();

            header("Location: dashboard.php");
        }
        elseif(isset($_POST["reject"])) 
        {
            $sql = "DELETE FROM friend_requests WHERE from_user = ? AND to_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $_POST["from_user"] , $_SESSION["username"]);
            $stmt->execute();
            header("Location: dashboard.php");
        }
    }
?>
<!DOCTYPE html>
<head>
    <title>Dashboard Page</title>
</head>
<body>
    <?php if(isset($_SESSION["username"])): ?>
    <h3>Welcome <?php echo $_SESSION["username"]?></h3>
    <form method="post" action="<?php htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <textarea type="text" name="context"></textarea>
        <button name="tweet" type="submit">Tweet</button><?php echo $tweetErr ?>
    </form><br>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
        <?php echo "<b>$row[owner]</b> tweeted \"$row[context]\" at $row[date]<br><br>" ?>
    <?php endwhile ?>
    <?php while($row = mysqli_fetch_assoc($requests)): ?>
        <?php echo "<b>$row[from_user]</b> sent you a friendship request at $row[date]" ?>
        <form method="post" action="<?php htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
            <input type="hidden" name="from_user" value="<?php echo $row["from_user"] ?>">
            <button name="accept" type="submit">Accept</button>
            <button name="reject" type="submit">Reject</button>
        </form>
    <?php endwhile ?>
    <h3>Friends</h3>
    <?php foreach($friends as $friend): ?>
        <?php echo $friend . "<br>" ?>
    <?php endforeach ?>
    <br><br>
    <a href="social.php">Meet New People!</a><br><br>
    <form method="post" action="<?php htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <button name="logout" type="submit">Logout</button>
    </form>
    <?php else: ?>
        <p>You are not logged in!</p><br><br>
        <a href='login.php'>Go to Login Page</a>
    <?php endif ?>
</body>
</html>