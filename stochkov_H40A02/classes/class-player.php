<?php
declare(strict_types=1);
class Player {
    private string $first_name;
    private string $last_name;
    private string $player_number;
    private string $city;
    private Country $country;
    private bool $is_professional;

    function __construct(string $first_name, string $last_name,
                         string $player_number, string $city,
                         Country $country, bool $is_professional) {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->player_number = $player_number;
        $this->city = $city;
        $this->country = $country;
        $this->is_professional = $is_professional;
    } // __construct()

    static function name_error(string $name) : string {
        if ($name === "")
            return "Name is required.";
        if (preg_match("/^[a-z][a-z-'\s]*[a-z]$/i", $name))
            return "";
        return "Please enter a valid name.";
    } // name_error

    static function city_error(string $city) : string {
        if ($city === "")
            return "City is required.";
        if (preg_match("/^[a-z][a-z-\s]*[a-z]$/i", $city))
            return "";
        return "Please enter a valid city name.";
    } // city_error

    static function country_error(string $country) : string {
        if ($country === Country::Select->name)
            return "Please select a country.";
        foreach (Country::cases() as $count) {
            if ($count->name === $country)
                return  "";
        }
            return "Please enter a valid country.";
    } // country_error

    function create_new_player() : string {
        return $this->player_number . "~" . $this->first_name . "~" . $this->last_name . "~"
            . $this->city . "~" . $this->country->name . "~" . ($this->is_professional ? "yes" : "no");
    } // create_new_player

    static function player_number_error(array $players, string $id, string $edit) : string {
        if ($id === "")
            return "Player number is required.";
        if (!is_numeric($id))
            return "Player number must be numeric.";
        if (intval($id) < 0)
            return "Player number must be above 0.";
        $players = array_values($players);
        if (sizeof($players) > 0) {
            foreach ($players as $i => $player) {
                if ($player->get_player_number() == $id && $i != $edit)
                    return "Player number must be unique.";
            } // foreach
        } // if
        return "";
    } // player_num_error

    static function img_error(array $img_array) : string {
        if ($img_array['size'] !== 0 && !is_array(getimagesize($img_array['tmp_name'])))
            return "File must be an image.";
        if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH']>5000)
            return 'File is too large.';
        return "";
    }

    static function get_image_path(string $num, string $email) : string {
        $dir = "./images/" . $email . "/";
        $dir_entries = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($dir_entries as $img) {
            if (pathinfo($img, PATHINFO_FILENAME) === $num)
                return $img;
        } // foreach
        $imagesDir = './default_images/';
        $images = glob($imagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        $random_image = $images[array_rand($images)]; // See comments
        return $random_image;
    } // get_image_path

    // setters & getters
    function get_first_name() : string {
        return $this->first_name;
    }
    function set_first_name(string $first_name) : void {
        $this->first_name = $first_name;
    }
    function get_last_name() : string {
        return $this->last_name;
    }
    function set_last_name(string $last_name) : void
    {
        $this->last_name = $last_name;
    }
    function get_player_number() : string {
        return $this->player_number;
    }
    function set_player_number(string $player_number) : void {
        $this->player_number = $player_number;
    }
    function get_city() : string {
        return $this->city;
    }
    function set_city(string $city) : void {
        $this->city = $city;
    }
    function get_country() : Country {
        return $this->country;
    }
    function set_country(Country $country) : void {
        $this->country = $country;
    }
    function get_is_professional() : bool {
        return $this->is_professional;
    }
    function set_is_professional(bool $is_professional) : void {
        $this->is_professional = $is_professional;
    }

} // Player class