<?php
session_start();

error_reporting(0);

if (isset($_SESSION['user']) && (!isset($_SESSION['user']['stdNumber']) || !isset($_SESSION['user']['realName']))) {
    unset($_SESSION['user']);
}

define('GZCTF_SERVER', getenv('GZCTF_SERVER') ?: 'http://localhost:8000');
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" href="github-markdown.css">
    <title>Writeup 上传</title>
    <style>
        body {
            max-width: 960px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <main class="markdown-body">
        <h1>Writeup 上传</h1>
        <section id="info">
            <p>你可以在这里上传「2022“虎踞龙蟠杯”东南大学第三届大学生网络安全挑战赛」的 Writeup。</p>
            <hr>
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>
                <?php
                if (isset($_POST['invite_token'])) {
                    $exp = explode(':', $_POST['invite_token']);
                    $context = stream_context_create(['http' => ['ignore_errors' => true]]);
                    $team_info = file_get_contents(GZCTF_SERVER . '/api/team/retrieve/1:1:' . end($exp), false, $context);
                    if ($team_info === false) {
                        echo '<p>无法连接至比赛平台，请联系管理员。</p>';
                    } else {
                        $team_info = json_decode($team_info, true);
                        if (isset($team_info['status'])) {
                            echo '<p>队伍邀请码无效，请检查后重新提交。</p>';
                        } else {
                            $user_info = file_get_contents(GZCTF_SERVER . '/api/account/id/' . $team_info['members'][0]['id']);
                            $decoded = json_decode($user_info, true);
                            if (isset($decoded['stdNumber']) && isset($decoded['realName'])) {
                                $_SESSION['user'] = $decoded;
                                echo '<script>location.href = location.href;</script>';
                            } else {
                                echo '<p>获取用户信息失败，请重试。如持续发生问题，请联系管理员。</p>';
                            }                            
                        }
                    }
                } else if (isset($_FILES['file'])) {
                    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                    if ($extension == '') {
                        echo '<p>文件名不合法，请检查后重新提交。</p>';
                    } else {
                        $file_name = $_SESSION['user']['stdNumber'] . '_' . $_SESSION['user']['realName'] . '_' . time() . '.' . $extension;
                        $file_path = 'writeups/' . $file_name;
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                            echo '<p>上传成功。</p>';
                            echo '<script>setTimeout(() => location.href = location.href, 1000)</script>';
                        } else {
                            echo '<p>上传失败，请检查后重新提交。如持续发生问题，请联系管理员。</p>';
                        }
                    }
                }
                ?>
            <?php endif; ?>
            <?php if ($_SERVER['REQUEST_METHOD'] === 'GET') : ?>
                <?php if (!isset($_SESSION['user'])) : ?>
                    <form method="post">
                        <p>输入你的队伍邀请码：<input name="invite_token"> <input type="submit" id="submit" value="提交"></p>
                    </form>
                    <p>队伍邀请码可以在「队伍管理」中队伍详细信息页面找到，它类似于 <code>123:4:f86xxxxxxxxxxxxxxxxxxxxxxxxxxxx5ba</code>。</p>
                <?php else : ?>
                    <p>你已经登录为 <code><?php echo $_SESSION['user']['stdNumber']; ?> <?php echo $_SESSION['user']['realName']; ?></code>。</p>
                    <form method="post" enctype="multipart/form-data">
                        <p>上传新的 Writeup：
                            <input type="file" name="file">
                            <input type="submit" id="submit" value="提交">
                        </p>
                    </form>
                    <?php
                    $files = scandir('writeups');
                    $list = [];
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..' || !preg_match('/^' . $_SESSION['user']['stdNumber'] . '_/', $file)) {
                            continue;
                        }
                        $list[] = '<li><a href="writeups/' . $file . '">' . $file . '</a></li>';
                    }
                    if (count($list) > 0) {
                        echo '<p>你已经上传过以下 Writeup：</p>';
                        echo '<ul>' . implode('', $list) . '</ul>';
                    }
                    ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>

<!-- copied from Hackergame 2022 「猜数字」 -->