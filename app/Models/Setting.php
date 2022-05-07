<?php
namespace App\Models;

class Setting extends Model
{
    protected $primaryKey = 'setting';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    static function allWithBG() {
        $settings = self::get()->keyBy('setting');
        if (isset($settings['parallax_background']) && !empty($settings['parallax_background'])) {
            if (filter_var($settings['parallax_background']['value'], FILTER_VALIDATE_URL)) {
                $path = parse_url($settings['parallax_background']['value'])['path'];
            } else {
                $path = $settings['parallax_background']['value'];
            }
            strtok($path, '.');
            $ext = strtok('');
            if (!in_array($ext, ['mp4', 'webm'])) {
                $settings['parallax_background']['type'] = "image/$ext";
            } else {
                $settings['parallax_background']['type'] = "video/$ext";
            }
        }
        return $settings;
    }
}
