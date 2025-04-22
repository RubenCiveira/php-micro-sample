<?php

namespace Civi\Repomanager\Features\Repository\Package;

class Package
{
    public string $id;
    public string $name;
    public string $url;
    public string $type;
    public string $status;
    public string $description;

    public static function from($data): Package
    {
        $pack = new Package();
        $pack->id = $data['id'];
        $pack->name = $data['name'];
        $pack->url = $data['url'];
        $pack->type = $data['type'];
        $pack->status = $data['status'];
        $pack->description = $data['description'];
        return $pack;
    }
    public function withStatus(string $status): Package
    {
        $pack = clone $this;
        $pack->status = $status;
        return $pack;
    }
}