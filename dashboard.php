<?php
    session_start();
    include "include/header.php";

    if(isset($_SESSION["username"])) 
    {
        $sql = "SELECT * FROM tweets WHERE owner = ? OR owner IN
        (SELECT friend1 FROM friends WHERE friend2 = ? UNION SELECT friend2 FROM friends WHERE friend1 = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $_SESSION["username"], $_SESSION["username"], $_SESSION["username"]);
        $stmt->execute();
        $result = $stmt->get_result();

        $sql = "SELECT friend1 FROM friends WHERE friend2 = ? UNION SELECT friend2 FROM friends WHERE friend1 = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $_SESSION["username"], $_SESSION["username"]);
        $stmt->execute();
        $friends = $stmt->get_result();

        $like_result = array();
        while($row = mysqli_fetch_assoc($result))
        {
            $sql = "SELECT * FROM likes WHERE tweet_id = ? AND liked_by = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $row["tweet_id"], $_SESSION["username"]);
            $stmt->execute();
            $row["liked"] = $stmt->get_result()->num_rows === 1;
            $like_result[] = $row;
        }
    
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
                $sql = "INSERT INTO tweets(context, owner, likes) VALUES(?, ?, '0')";
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
        elseif(isset($_POST["like_x"]))
        {
            $sql = "INSERT INTO likes (tweet_id, liked_by) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $_POST["tweet_id"], $_SESSION["username"]);
            $stmt->execute();   
            header("Location: dashboard.php");
        }
        elseif(isset($_POST["not_like_x"]))
        {
            $sql = "DELETE FROM likes WHERE tweet_id = ? AND liked_by = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $_POST["tweet_id"], $_SESSION["username"]);
            $stmt->execute();
            header("Location: dashboard.php");            
        }
    }
?>
<!DOCTYPE html>
<body>
    <?php if(isset($_SESSION["username"])): ?>
    <h3>Welcome <?php echo $_SESSION["username"]?></h3>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <textarea type="text" name="context"></textarea>
        <button name="tweet" type="submit">Tweet</button><?php echo $tweetErr ?>
    </form><br>
    <?php foreach($like_result as $row): ?>
        <?php echo "<b>$row[context]</b> tweeted by \"$row[owner]\" at $row[date]" ?>
        <?php if($row["liked"]): ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                <input type="hidden" name="tweet_id" value="<?php echo $row["tweet_id"] ?>">
                <input type="image" src="img/full_heart.png" height="30" width="30" name="not_like">
                <?php echo $row["likes"] ?>
            </form>
        <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                <input type="hidden" name="tweet_id" value="<?php echo $row["tweet_id"] ?>">
                <input type="image" src="img/empty_heart.png" height="30" width="30" name="like">
                <?php echo $row["likes"] ?>
            </form>
        <?php endif ?>
    <?php endforeach ?>
    <?php while($row = mysqli_fetch_assoc($requests)): ?>
        <?php echo "<b>$row[from_user]</b> sent you a friendship request at $row[date]" ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
            <input type="hidden" name="from_user" value="<?php echo $row["from_user"] ?>">
            <button name="accept" type="submit">Accept</button>
            <button name="reject" type="submit">Reject</button>
        </form>
    <?php endwhile ?>
    <br>
    <h3>Friends</h3>
    <?php while($row = mysqli_fetch_assoc($friends)): ?>
        <?php echo $row["friend1"] . "<br>" ?>
    <?php endwhile ?>
    <br><br>
    <a href="social.php">Meet New People!</a><br><br>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <button name="logout" type="submit">Logout</button>
    </form>
    <?php else: ?>
        <p>You are not logged in!</p><br><br>
    <?php endif ?>
<?php include "include/footer.php" ?>