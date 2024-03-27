<?php
declare(strict_types=1);
require "./class-player.php";
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
                $leader_arr = explode("~", $leader);
                if ($email === $leader_arr[1])
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
                $leader_arr = explode("~", $leader);
                if ($email === $leader_arr[1]) {
                    if (password_verify($pass, $leader_arr[2]))
                        return "";
                } // if email exists
            } // foreach
        } // if
        return "Invalid email or password.";
    } // login_error()

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

    function create_new_leader() : string {
        return $this->name . "~" . $this->email_address . "~" . $this->password;
    } // create_new_leader()

    function add_to_players(Player $player) : bool {
        array_push($this->player_array, $player);
        return true;
    } // add_to_players()

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