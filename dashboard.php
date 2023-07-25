<?php
    session_start();
    include "include/header.php";

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
            $sql = "SELECT * FROM tweets WHERE owner = ? OR owner IN (";
            $friendPlaceholders = implode(',', array_fill(0, count($friends), '?'));
            $sql .= $friendPlaceholders . ") ORDER BY date DESC";
            $stmt = $conn->prepare($sql);

            $bindParams = array_merge(array($_SESSION["username"]), $friends);
            $types = str_repeat('s', count($bindParams));

            $stmt->bind_param($types, ...$bindParams);
        }
        else
        {
            $sql = "SELECT * FROM tweets WHERE owner = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $_SESSION["username"]);
        }
        $stmt->execute();
      
        $result = $stmt->get_result();
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
        elseif(isset($_POST["like_x"]))
        {
            $sql = "UPDATE tweets SET likes = likes + 1 WHERE tweet_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $_POST["tweet_id"]);
            $stmt->execute();
            
            $sql = "INSERT INTO likes (tweet_id, liked_by) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $_POST["tweet_id"], $_SESSION["username"]);
            $stmt->execute();
            
            header("Location: dashboard.php");
        }
        elseif(isset($_POST["not_like_x"]))
        {
            $sql = "UPDATE tweets SET likes = likes - 1 WHERE tweet_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $_POST["tweet_id"]);
            $stmt->execute();

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
    <?php foreach($friends as $friend): ?>
        <?php echo $friend . "<br>" ?>
    <?php endforeach ?>
    <br><br>
    <a href="social.php">Meet New People!</a><br><br>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <button name="logout" type="submit">Logout</button>
    </form>
    <?php else: ?>
        <p>You are not logged in!</p><br><br>
    <?php endif ?>
<?php include "include/footer.php" ?>