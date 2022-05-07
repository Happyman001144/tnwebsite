<?php
namespace App\Models;

class Server extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function getImageURLAttribute() {
      if (!isset($this->image) && empty($this->image)) {
          if ($this->game === 'gmod') {
              return 'https://steamcdn-a.akamaihd.net/steam/apps/4000/header.jpg';
          } else if ($this->game === 'rust') {
              return 'https://steamcdn-a.akamaihd.net/steam/apps/252490/header.jpg';
          } else if ($this->game === 'csgo') {
              return 'https://steamcdn-a.akamaihd.net/steam/apps/730/header.jpg';
          } else if ($this->game === 'minecraft') {
              return 'https://steam.cryotank.net/wp-content/gallery/minecraft/Minecraft-07-HD.png';
          } else if ($this->game === 'ark') {
              return 'https://steamcdn-a.akamaihd.net/steam/apps/346110/header.jpg';
          } else if ($this->game === 'arma3') {
              return 'https://steamcdn-a.akamaihd.net/steam/apps/107410/header.jpg';
          }
      } else {
          return $this->image;
      }
    }

    public function packages() {
        return $this->hasMany('App\Models\StorePackage', 'server', 'id');
    }
}
