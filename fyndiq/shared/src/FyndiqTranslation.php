<?php

/**
 * Class FyndiqTranslation is used to manage translations
 */
class FyndiqTranslation
{
    const DEFAULT_LANGUAGE = 'en';
    const MISSING_TRANSLATION_PREFIX = 'NT: ';

    /**
     * @var array holds the current translation messages
     */
    private static $messages = array();

    /**
     * Load language translation from file
     * @param string $filePath
     * @return bool|array false or the translation array
     */
    private static function loadLanguage($filePath)
    {
        if (file_exists($filePath)) {
            $lang = false;
            include($filePath);
            return $lang;
        }
        return false;
    }

    /**
     * Returns the file name for the language code
     *
     * @param string $isoLanguageCode
     * @return string
     */
    public static function getFileName($isoLanguageCode)
    {
        return realpath(dirname(__FILE__) . '/../translations/' . $isoLanguageCode . '.php');
    }

    /**
     * Initializes the class with the language code
     *
     * @param string $isoLanguageCode
     * @return bool
     */
    public static function init($isoLanguageCode = '')
    {
        $isoLanguageCode = empty($isoLanguageCode) ? self::DEFAULT_LANGUAGE: $isoLanguageCode;
        $isoLanguageCode = strtolower($isoLanguageCode);

        if (strpos($isoLanguageCode, '_') !== false) {
            $langArray = explode('_', $isoLanguageCode);
            $isoLanguageCode = reset($langArray);
        }

        foreach (array_unique(array($isoLanguageCode, self::DEFAULT_LANGUAGE)) as $language) {
            $filePath = self::getFileName($language);
            $result = self::loadLanguage($filePath);
            if ($result) {
                self::$messages = $result;
                return true;
            }
        }
        return false;
    }

    /**
     * Returns translation for single string
     *
     * @param string $name key name
     * @return string resulting translation
     */
    public static function get($name)
    {
        $missingPrefix = '';
        if (class_exists('FyndiqUtils') && FyndiqUtils::isDebug()) {
            $missingPrefix = self::MISSING_TRANSLATION_PREFIX;
        }
        return empty(self::$messages[$name]) ? $missingPrefix . $name : self::$messages[$name];
    }

    /**
     * Returns all translations as key=>value
     *
     * @return array translations
     */
    public static function getAll()
    {
        return self::$messages;
    }
}
