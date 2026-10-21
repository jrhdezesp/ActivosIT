<?php

namespace Utilities;

class Nav
{
    public static function setPublicNavContext()
    {
        $isDevelopment = Context::getContextByKey("DEVELOPMENT") === "1";
        $tmpNAVIGATION = $isDevelopment ? "" : Context::getContextByKey("PUBLIC_NAVIGATION");
        if (!is_array($tmpNAVIGATION) || count($tmpNAVIGATION) === 0) {
            $navigationData = self::getNavFromJson()["public"];
            Context::setContext("PUBLIC_NAVIGATION", $navigationData, !$isDevelopment);
        }
    }
    public static function setNavContext()
    {
        $isDevelopment = Context::getContextByKey("DEVELOPMENT") === "1";
        $tmpNAVIGATION = $isDevelopment ? "" : Context::getContextByKey("NAVIGATION");
        if (!is_array($tmpNAVIGATION) || count($tmpNAVIGATION) === 0) {
            $tmpNAVIGATION = [];
            $userID = Security::getUserId();
            $navigationData = self::getNavFromJson()["private"];
            foreach ($navigationData as $navEntry) {
                if (
                    $isDevelopment
                    || Security::isAuthorized($userID, $navEntry["id"], 'MNU')
                ) {
                    $tmpNAVIGATION[] = $navEntry;
                }
            }
            Context::setContext("NAVIGATION", $tmpNAVIGATION, !$isDevelopment);
        }
    }

    public static function invalidateNavData()
    {
        Context::removeContextByKey("NAVIGATION_DATA");
        Context::removeContextByKey("NAVIGATION");
        Context::removeContextByKey("PUBLIC_NAVIGATION");
    }

    private static function getNavFromJson()
    {
        $isDevelopment = Context::getContextByKey("DEVELOPMENT") === "1";
        $jsonContent = $isDevelopment ? "" : Context::getContextByKey("NAVIGATION_DATA");
        if ($jsonContent === "") {
            $filePath = __DIR__ . '/../../nav.config.json';
            if (!file_exists($filePath)) {
                throw new \Exception(sprintf('%s does not exist', $filePath));
            }
            if (!is_readable($filePath)) {
                throw new \Exception(sprintf('%s file is not readable', $filePath));
            }
            $jsonContent = file_get_contents($filePath);
            Context::setContext("NAVIGATION_DATA", $jsonContent, !$isDevelopment);
        }
        $jsonData = json_decode($jsonContent, true);
        return $jsonData;
    }

    private function __construct()
    {
    }
    private function __clone()
    {
    }
}
