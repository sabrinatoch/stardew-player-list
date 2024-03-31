<?php
declare(strict_types=1);
ini_set('display_errors', "1");
error_reporting(E_ALL);
ini_set('display_startup_errors', "1");
header("Content-Security-Policy: default-src 'self'; font-src 'self' https://fonts.cdnfonts.com https://db.onlinewebfonts.com data:; style-src 'self' https://fonts.cdnfonts.com https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://stackpath.bootstrapcdn.com 'unsafe-inline'; frame-ancestors 'none'; form-action 'self'");
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// files
require "./enums/enum-country.php";
require "./classes/class-leader.php";

function generateCSRFToken() {
    return bin2hex(random_bytes(32));
} // generateCSRFToken()

function isCSRFTokenValid($token) : bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
} // isCSRFTokenValid

// variables
$action = $_GET["action"] ?? "";
$display_login = $action === "";

$editing = false;
$index_to_delete = $_GET["delete"] ?? "";
$index_to_edit = $_GET["update"] ?? "";

$leader_array = [];

$logged_leader = new Leader("", "", "");

if (!file_exists("./images"))
    mkdir("./images");

// getting the leader
if (isset($_SESSION["leader"])) {
    $logged_leader = unserialize($_SESSION["leader"]);
    if (count($logged_leader->get_player_array()) == 0) {
        if (!file_exists("./images/" . $logged_leader->get_email_address()))
            mkdir("./images/" . $logged_leader->get_email_address());
    } // else
}

function reset_session($logged) : Leader {
    $_SESSION["leader"] = serialize($logged);
    return unserialize($_SESSION["leader"]);
} // reset_session()

// populating the leaders_file array
if (file_exists("./leader-data.txt") && filesize("./leader-data.txt") > 0) {
    $leader_file = file("./leader-data.txt", FILE_IGNORE_NEW_LINES);
    foreach ($leader_file as $leader_line) {
        $line = unserialize($leader_line);
        array_push($leader_array, $line);
    }
} // if
else
    $open_file = fopen("./leader-data.txt", "w");


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
} // if CSRF token not set

// LOGIN STUFF
$login_info = [
        "email" => $_POST["email"] ?? "",
        "password" => $_POST["password"] ?? ""
];
$login_err = "";

// POST FOR LOGIN
if (isset($_POST["login"])) {

    if (!isCSRFTokenValid($_POST['csrf_token']))
        exit('Invalid CSRF token.'); // validate token

    $login_err = Leader::login_error($login_info["email"], $login_info["password"], $leader_array);
    if ($login_err === "") {
        $display_login = false;
        session_regenerate_id();
        $logged_leader = Leader::get_leader_object($login_info["email"]);
        $_SESSION["leader"] = serialize($logged_leader);
        $_SESSION["time_logged"] = date("D M d, Y G:i");
    } // if logged in
    else {
        $display_login = true;
        $action = "";
    } // else
} // login

// form fields
$new_player = [
        "first" => $_POST["fName"] ?? "",
        "last" => $_POST["lName"] ?? "",
        "number" => $_POST["num"] ?? "",
        "city" => $_POST["city"] ?? "",
        "country" => $_POST["country"] ?? "",
        "prof" => $_POST["prof"] ?? ""
];
$new_leader = [
  "name" => $_POST["name"] ?? "",
  "email" => $_POST["new_email"] ?? "",
  "pass" =>  $_POST["new_password"] ?? "",
  "confirm" => $_POST["confirm_password"] ?? ""
];

// objects
$player_obj = new Player("", "", "", "", Country::Select, false);
$leader_obj = new Leader("", "", "");

// error messages
$err = [
    "first" => "",
    "last" => "",
    "number" => "",
    "city" => "",
    "img" => ""
];
$account_err = [
  "name" => "",
  "email" => "",
  "pass" => "",
  "confirm" => "",
];

// DELETING
if ($index_to_delete !== "") {
    // popup "Are you sure you want to delete this player?"
    if (array_key_exists($index_to_delete, $logged_leader->get_player_array())) {
        $logged_leader->delete_player(intval($index_to_delete));
        $logged_leader = reset_session($logged_leader);
        Leader::save_leaders($leader_array, $logged_leader);
        header('Location: ./player-league.php?action=view');
    } // if
    else {
        header('Location: ./player-league.php');
    } // else
} // delete

// EDITING
if ($index_to_edit !== "") {
    if (array_key_exists($index_to_edit, $logged_leader->get_player_array())) {
        $player_to_edit = $logged_leader->get_player_array()[$index_to_edit];
        $new_player["number"] = $player_to_edit->get_player_number();
        $new_player["first"] = $player_to_edit->get_first_name();
        $new_player["last"] = $player_to_edit->get_last_name();
        $new_player["city"] = $player_to_edit->get_city();
        $new_player["country"] = $player_to_edit->get_country()->value;
        $new_player["prof"] = $player_to_edit->get_is_professional();
        $editing = true;
        $display_login = false;
    } // if
    else {
        header('Location: ./player-league.php');
    } // else
} // edit

// POST FOR CREATE LEADER ACCOUNT
if (isset($_POST["submit_account"])) {
    if (!isCSRFTokenValid($_POST['csrf_token']))
        exit('Invalid CSRF token.'); // validate token
    $account_err["name"] = Player::name_error($new_leader["name"]);
    $account_err["email"] = Leader::email_error($new_leader["email"], $leader_array);
    $account_err["pass"] = Leader::password_error($new_leader["pass"]);
    $account_err["confirm"] = Leader::confirm_password($new_leader["pass"], $new_leader["confirm"]);

    if (empty($account_err["name"]) && empty($account_err["email"]) &&
        empty($account_err["pass"]) && empty($account_err["confirm"])) {
        $leader_obj = new Leader(
                $new_leader["name"],
                $new_leader["email"],
                password_hash($new_leader["pass"], PASSWORD_BCRYPT)
        );

        array_unshift($leader_array, $leader_obj);
        $leader_array = array_values($leader_array);
        Leader::save_leaders($leader_array, $logged_leader);
        $display_login = true;
    } // if no errors
    else {
        $display_login = false;
        $action = "account";
    } // else
} // create leader

// POST FOR CREATE/EDIT PLAYER
if (isset($_POST["create"]) || isset($_POST["save"])) {

        if (!isCSRFTokenValid($_POST['csrf_token']))
            exit('Invalid CSRF token.'); // validate token

        $index_to_edit = $_POST["editIndex"] ?? "";

        $err["first"] = Player::name_error($new_player["first"]);
        $err["last"] = Player::name_error($new_player["last"]);
        $err["city"] = Player::city_error($new_player["city"]);
        $err["country"] = Player::country_error($_POST["country"]);
        $err["number"] = Player::player_number_error($logged_leader->get_player_array(), $_POST["num"], $index_to_edit);
        $err["img"] = Player::img_error($_FILES['img']);

        if (empty($err["first"]) && empty($err["last"]) && empty($err["img"]) &&
            empty($err["number"]) && empty($err["city"])) {

            $player_obj = new Player(
                $new_player["first"],
                $new_player["last"],
                $new_player["number"],
                $new_player["city"],
                Country::tryFrom($new_player["country"]),
                isset($_POST["prof"])
            );

            if ($index_to_edit !== "")
                $logged_leader->delete_player(intval($index_to_edit));

            $logged_leader->add_to_players($player_obj);

            if ($_FILES['img']['size'] > 0) {
                $dest = "./images/" . $logged_leader->get_email_address() . "/" . $_POST["num"] . substr($_FILES['img']['name'], strpos($_FILES['img']['name'], '.'));
                move_uploaded_file($_FILES['img']['tmp_name'], $dest);
            } // if image was uploaded
            $logged_leader = reset_session($logged_leader);
            Leader::save_leaders($leader_array, $logged_leader);
        } // if no errors
        else {
            $display_login = false;
            if (isset($_POST["create"]))
                $action = "add";
            else
                $editing = true;
        } // else

        $display_login = false;
} // create/edit player

// LOGGING OUT
if ($action === "logout") {
    session_destroy();
    $display_login = true;
} // logout

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stardew Valley</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/site.css"/>
</head>
<body>

<?php if ($display_login) : ?>
<div class="center">
    <h2>Stardew Valley</h2>
    <form id="loginForm" method="post" action="./player-league.php?action=view" class="stardew-form">
        <p>Don't have an account? <a href="./player-league.php?action=account">Create one!</a></p>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="text" name="email" id="email" class="form-control <?php echo $login_err !== "" ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($login_info["email"])?>"/>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control <?php echo $login_err !== "" ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($login_info["password"])?>"/>
        </div>
        <div class="error">
            <?php if ($login_err !== "") :?>
                <p><?=$login_err?></p>
            <?php endif; ?>
        </div>
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>" />
        <div class="right">
            <input type="submit" name="login" id="login" value="Login" class="btn stardew-btn">
        </div>
    </form>
</div>

<?php elseif ($action === "account") :?>
    <div class="center">
        <h2>Create Account</h2>
        <form id="accountForm" method="post" action="./player-league.php?action=view" class="stardew-form">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control <?php echo $account_err["name"] !== "" ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_leader["name"])?>" />
                <div class="error">
                    <?php if (!empty($account_err["name"])) :?>
                        <p><?=$account_err["name"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="new_email" class="form-label">Email Address</label>
                <input type="text" name="new_email" id="new_email" class="form-control <?php echo $account_err["email"] !== "" ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_leader["email"])?>" />
                <div class="error">
                    <?php if (!empty($account_err["email"])) :?>
                        <p><?=$account_err["email"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control <?php echo $account_err["pass"] !== "" ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_leader["pass"])?>" />
                <div class="error">
                    <?php if (!empty($account_err["pass"])) :?>
                        <p><?=$account_err["pass"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo $account_err["confirm"] !== "" ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_leader["confirm"])?>" />
                <div class="error">
                    <?php if (!empty($account_err["confirm"])) :?>
                        <p><?=$account_err["confirm"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>" />
            <div class="right">
                <input type="submit" name="submit_account" id="submit_account" value="Create" class="btn stardew-btn">
            </div>
        </form>
    </div>


<?php elseif ($action === "view") : ?>
    <p class="logged-in">Logged in as: <?=$logged_leader->get_name()?> since <?=$_SESSION["time_logged"]?></p>
    <h2>Players</h2>
<a class="create-btn" href="./player-league.php?action=add"><button name="add" id="add" class="btn stardew-btn">New Player</button></a>
<a class="logout-btn" href="./player-league.php?action=logout"><button name="logout" id="logout" class="btn stardew-btn">Log Out</button></a>
<div class="players-container">
    <?php foreach ($logged_leader->get_player_array() as $index => $player) :?>
        <div class="player">
            <a class="edit" href="./player-league.php?update=<?= $index ?>"></a>
            <a class="del" href="./player-league.php?delete=<?= $index ?>"></a>
            <?php $img_path = Player::get_image_path($player->get_player_number(), $logged_leader->get_email_address()); ?>
            <img src="<?=$img_path?>" width="210px"/>
            <p><?=$player->get_first_name() . " " . $player->get_last_name()?></p>
            <p><?="Player #" . $player->get_player_number()?></p>
            <p><?=$player->get_city() . ", " . $player->get_country()->value?></p>
            <p>Professional: <?=($player->get_is_professional() ? "yes" : "no")?></p>
        </div>
    <?php endforeach; ?>
</div>


<?php elseif ($action === "add" || $editing ) :?>
    <div class="center">
        <?php if ($action === "add") :?>
        <h2>Create Player</h2>
        <form method="post" enctype="multipart/form-data" action="./player-league.php?action=added" class="stardew-form form">
        <?php elseif ($editing) :?>
        <h2>Edit Player</h2>
            <form method="post" enctype="multipart/form-data" action="./player-league.php?action=updated" class="stardew-form form">
        <?php endif; ?>
            <div class="mb-2">
                <label for="fName" class="form-label">First Name</label>
                <input type="text" name="fName" id="fName" class="form-control <?php echo $err["first"] ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_player["first"])?>"/>
                <div class="error">
                    <?php if (!empty($err["first"])) :?>
                        <p><?=$err["first"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-2">
                <label for="lName" class="form-label">Last Name</label>
                <input type="text" name="lName" id="lName" class="form-control <?php echo $err["last"] ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_player["last"])?>"/>
                <div class="error">
                    <?php if (!empty($err["last"])) :?>
                        <p><?=$err["last"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-2">
                <label for="num" class="form-label">Player Number</label>
                <input type="text" name="num" id="num" class="form-control <?php echo $err["number"] ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_player["number"])?>"/>
                <div class="error">
                    <?php if (!empty($err["number"])) :?>
                        <p><?=$err["number"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-2">
                <label for="city" class="form-label">City</label>
                <input type="text" name="city" id="city" class="form-control <?php echo $err["city"] ? 'err-input' : ''; ?>" value="<?=htmlspecialchars($new_player["city"])?>"/>
                <div class="error">
                    <?php if (!empty($err["city"])) :?>
                        <p><?=$err["city"]?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="side">
                <div>
                    <label for="country" class="form-label">Country</label>
                    <select name="country" >
                        <?php foreach (Country::cases() as $country) :?>
                        <option value="<?=$country->value?>"
                            <?php if ($country->value === htmlspecialchars($new_player["country"])) echo "selected" ?>>
                            <?=$country->value?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error">
                        <?php if (!empty($err["country"])) : ?>
                            <p><?=$err["country"]?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label for="prof">Is Professional?</label>
                    <input type="checkbox" name="prof" id="prof" <?php if ($new_player["prof"]) : ?> checked<?php endif;?> />
                </div>
            </div>
            <div>
                <label for="img">Image:</label><br>
                <input type="file" name="img" id="img" class="<?php echo $err["img"] ? 'err-input' : ''; ?>"/>
                <div class="error">
                    <?php if (!empty($err["img"]) && is_uploaded_file($_FILES['img']['tmp_name'])) :?>
                        <p><?=$err["img"]?></p>
                    <?php endif; ?>
                </div>
            </div>
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>" />
                <div class="right">
                <?php if ($editing) :?>
                    <input type="hidden" name="editIndex" value="<?=$index_to_edit?>">
                    <input type="submit" name="save" id="save" value="Save" class="btn stardew-btn">
                <?php else :?>
                    <input type="submit" name="create" id="create" value="Create" class="btn stardew-btn">
                <?php endif; ?>
            </div>
        </form>
    </div>


<?php elseif ($action === "added") :?><br>
    <div class="alert alert-primary added" role="alert">
        <h4 class="alert-heading">Player Added</h4>
        <p>Successfully added <?=$player_obj->get_first_name()?> <?=$player_obj->get_last_name()?> (<?=$player_obj->get_player_number()?>) to your team.</p>
        <hr>
        <a href="./player-league.php?action=view"><button name="view" id="view" class="btn stardew-btn">View Players</button></a>
    </div>


<?php elseif ($action === "updated") :?><br>
    <div class="alert alert-primary added" role="alert">
        <h4 class="alert-heading">Player Updated</h4>
        <p>Successfully updated <?=$player_obj->get_first_name()?> <?=$player_obj->get_last_name()?> (<?=$player_obj->get_player_number()?>) on your team.</p>
        <hr>
        <a href="./player-league.php?action=view"><button name="view" id="view" class="btn stardew-btn">View Players</button></a>
    </div>

<?php endif; ?>


</body>
</html>