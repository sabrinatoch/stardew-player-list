<?php
declare(strict_types=1);
require "./classes/class-player.php";
class Leader {
    private string $name;
    private string $email_address;
    private string $password;
    private array $player_array;

    function __construct(string $name, string $email_address, string $password) {
        $this->name = $name;
        $this->email_address = $email_address;
        $this->password = $password;
        $this->player_array = [];
    } // __construct()

    static function email_error(string $email, array $leaders) : string {
        if ($email === "")
            return "Email address is required.";
        else if (!preg_match('/^[a-z][\w.%+-]*@[\w.-]+\.[a-z]{2,}$/i', $email))
            return "Invalid email address.";
        $leaders = array_values($leaders);
        if (sizeof($leaders) > 0) {
            foreach ($leaders as $leader) {
                if ($email === $leader->get_email_address())
                    return "An account with that email already exists.";
            } // foreach
        } // if
        return "";
    } // email_error()

    static function password_error(string $pass) : string {
        if ($pass === "")
            return "Password is required.";
        else if (!preg_match("/[0-9]/", $pass))
            return "Password must contain a number.";
        else if (!preg_match("/[a-z]/", $pass))
            return "Password must contain a lowercase character.";
        else if (!preg_match("/[A-Z]/", $pass))
            return "Password must contain an uppercase character.";
        else if (preg_match("/\s/", $pass))
            return "Password cannot contain spaces.";
        else if (!preg_match("/[^A-Za-z0-9]/", $pass))
            return "Password must contain a special character.";
        else if (strlen($pass) < 8 || strlen($pass) > 16)
            return "Password must be 8-16 characters long.";
        else
            return "";
    } // password_error()

    static function confirm_password(string $pass, string $confirm) : string {
        return ($pass === $confirm) ? "" : "Passwords must match.";
    } // confirm_password()

    static function login_error(string $email, string $pass, array $leaders) : string {
        if ($email === "" || $pass === "")
            return "Please enter your email and password.";
        $leaders = array_values($leaders);
        if (sizeof($leaders) > 0) {
            foreach ($leaders as $leader) {
                if ($email === $leader->get_email_address()) {
                    if (password_verify($pass, $leader->get_password()))
                        return "";
                } // if email exists
            } // foreach
        } // if
        return "Invalid email or password.";
    } // login_error()

    static function get_leader_object(string $email) : ?Leader {
        $leaders = file("./leader-data.txt");
        foreach($leaders as $lead) {
            $obj = unserialize($lead);
            if ($email === $obj->get_email_address())
                return $obj;
        }
        return null;
    } // get_leader_object()

    static function get_leader_name(string $email, array $leaders) : string {
        $leaders = array_values($leaders);
        if (sizeof($leaders) > 0) {
            foreach ($leaders as $leader) {
                $leader_arr = explode("~", $leader);
                if ($email === $leader_arr[1]) {
                   return $leader_arr[0];
                } // if
            } // foreach
        } // if
        return "";
    } // get_leader_name()

    function delete_player(int $index) : array {
        $dir = "./images/" . $this->email_address . "/";
        $dir_entries = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($dir_entries as $img) {
            if (pathinfo($img, PATHINFO_FILENAME) == $this->player_array[$index]->get_player_number())
                unlink($img);
        } // foreach
        unset($this->player_array[$index]);
        $this->player_array = array_values($this->player_array);
        return $this->player_array;
    } // delete_player

    function by_name($a, $b) : int {
        $last_name_comp = strcmp(strtolower($a->get_last_name()), strtolower($b->get_last_name()));
        if ($last_name_comp === 0)
            return strcmp(strtolower($a->get_first_name()), strtolower($b->get_first_name()));
        return $last_name_comp;
    } // by_name

    function add_to_players(Player $player) : void {
        array_push($this->player_array, $player);
//        $this->player_array = array_values($this->player_array);
        usort($this->player_array, array($this,'by_name'));
    } // add_to_players()

    static function save_leaders(array $leaders, Leader $logged) : void {
        foreach ($leaders as $i => $lead) {
            if ($lead->get_email_address() == $logged->get_email_address()) {
                unset($leaders[$i]);
                array_push($leaders, $logged);
            }
        }
        $file = fopen("./leader-data.txt", "w");
        foreach($leaders as $obj) {
            fwrite($file, serialize($obj) . "\n");
        }
        fclose($file);
    } // save_leaders

    // setters & getters
    function get_name() : string {
        return $this->name;
    }
    function set_name(string $name) : void {
        $this->name = $name;
    }
    function get_email_address() : string {
        return $this->email_address;
    }
    function set_email_address(string $email_address) : void {
        $this->email_address = $email_address;
    }
    function get_password() : string {
        return $this->password;
    }
    function set_password(string $password) : void {
        $this->password = $password;
    }
    function get_player_array() : array {
        return $this->player_array;
    }
    function set_player_array(array $player_array) : void {
        $this->player_array = $player_array;
    }
} // Leader class