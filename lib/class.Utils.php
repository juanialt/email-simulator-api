<?php
/**
 * Utils
 */

class Utils
{
    const PARAM_TYPE_INT = 0;
    const PARAM_TYPE_FLOAT = 1;
    const PARAM_TYPE_STRING = 2;
    const PARAM_TYPE_BOOL = 3;
    const PARAM_TYPE_RAW = 4;

    /**
     * Return POST | GET param
     *
     * @param string $sParamName
     * @param int $iParamType LIB_PARAM_TYPE_INT | LIB_PARAM_TYPE_FLOAT | LIB_PARAM_TYPE_STRING | LIB_PARAM_TYPE_BOOL | LIB_PARAM_TYPE_RAW
     * @return mixed
     */
    public static function getParam($sParamName, $iParamType = LIB_PARAM_TYPE_RAW)
    {
        if (!$sParamName) {
            return null;
        }
        $sResult = null;
        if (isset($_POST[$sParamName])) {
            $sResult = $_POST[$sParamName];
        } elseif (isset($_GET[$sParamName])) {
            $sResult = $_GET[$sParamName];
        }

        switch ($iParamType) {
            case LIB_PARAM_TYPE_INT:
                return (int) $sResult;
                break;
            case LIB_PARAM_TYPE_FLOAT:
                return (float) $sResult;
                break;
            case LIB_PARAM_TYPE_BOOL:
                return (bool) $sResult;
                break;
            case LIB_PARAM_TYPE_STRING:
                return (string) $sResult;
                break;
            default:
                return $sResult;
                break;
        }
    }

    /**
     * Returns an instance of an object with the attributes that starts with $sSuffix
     **/
    public static function move_attributes($sSuffix, &$oFrom)
    {
        $oResult = null;
        $vArray = get_object_vars($oFrom);
        if (is_array($vArray)) {
            foreach ($vArray as $varName => $value) {
                if (strstr($varName, $sSuffix)) {
                    $sVarName = str_replace($sSuffix, "", $varName);
                    $oResult->$sVarName = $value;
                    unset($oFrom->$varName);
                }
            }
        }
        return $oResult;
    }

    /**
     * Return DateTime with format ddmmyyyy hh:mm:ss
     *
     * @param string $sDateTime input string must be yyyymmdd H:i:s
     * @param bool $bUseTime
     * @return string
     */
    public static function date_to_ddmmyyyy($sDateTime, $bUseTime = true)
    {
        global $sDateFormat;

        if (strlen($sDateTime) < 8) {
            return null;
        }
        list($sDate, $sTime) = split(" ", $sDateTime);
        list($iYear, $iMonth, $iDay) = split("[/.-]", $sDate);

        switch ($sDateFormat) {
            case "ddmmyyyy":
                $sDate = $iDay . "/" . $iMonth . "/" . $iYear;
                break;
            case "yyyymmdd":
                $sDate = $iYear . "/" . $iMonth . "/" . $iDay;
                break;
        }

        return $bUseTime ? $sDate . " $sTime" : $sDate;
    }

    /**
     * Return Time with format HHmm hh:mm:ss
     *
     * @param string $sDateTime input string must be yyyymmdd H:i:s
     * @param bool $bUseTime
     * @return string
     */
    public static function date_to_HHmmss($sDateTime, $bUseSeconds = true)
    {
        if (strlen($sDateTime) < 8) {
            return null;
        }
        list($sDate, $sTime) = split(" ", $sDateTime);
        if (!$bUseSeconds) {
            list($iHour, $iMin, $iSec) = split("[/:]", $sTime);
            $sTime = $iHour . ":" . $iMin;
        }
        return $sTime;
    }

    /**
     * @param string $sDateTime (DDMMYYYY HHMMSS)
     * @param string
     */
    public static function ddmmyyyy_to_mysql($sDateTime, $bUseTime = false)
    {
        global $sDateFormat;

        if (strlen($sDateTime) < 8) {
            return null;
        }

        list($sDate, $sTime) = split(" ", $sDateTime);

        switch ($sDateFormat) {
            case "ddmmyyyy":
                list($iDay, $iMonth, $iYear) = split("[/.-]", $sDate);
                break;
            case "yyyymmdd":
                list($iYear, $iMonth, $iDay) = split("[/.-]", $sDate);
                break;
        }

        return $bUseTime ? $iYear . "-" . $iMonth . "-" . $iDay . " $sTime" : $iYear . "-" . $iMonth . "-" . $iDay;
    }

    /**
     * Send email
     *
     * @param string $sSubject
     * @param string $sBody
     * @param string $sFromName
     * @param string $sFromAddress
     * @param array $vTo
     * @param array $vAttach
     * @return bool
     */
    public static function sendEmail($sSubject, $sBody, $sFromName, $sFromAddress, $vTo, $vAttach = null)
    {
        include_once "./system/mailer/class.phpmailer.php";

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPDebug = 2;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PWD;

        $mail->Subject = $sSubject;
        $mail->SetFrom($sFromAddress, $sFromName);
        $mail->MsgHTML($sBody);

        if (is_array($vTo)) {

            foreach ($vTo as $to) {
                $mail->AddAddress($to->sAddress, $to->sName);
            }
        }

        if (is_array($vAttach)) {
            foreach ($vAttach as $attach) {
                $mail->AddAttachment($attach->sPath, $attach->sName);
            }
        }

        return $mail->Send();
    }

    /**
     * Get float number from string
     *
     * @param string $sValue
     * @return float or null
     */
    public static function getFloat($sValue)
    {
        $cColon = 0;
        $cPoints = 0;
        for ($i = 0; $i < strlen($sValue); $i++) {
            if (substr($sValue, $i, 1) == ',') {
                $cColon++;
            } else if (substr($sValue, $i, 1) == '.') {
                $cPoints++;
            }
        }

        if ((($cColon == 0) && ($cPoints == 0)) || (($cColon == 1) && ($cPoints == 0)) || (($cColon == 0) && ($cPoints == 1))) {
            $sValue = str_replace(',', '.', $sValue);
            return ((float) ($sValue));

        } else if (($cColon == 1) && ($cPoints == 1) && (strpos($sValue, ',') < strpos($sValue, '.'))) {
            $sValue = str_replace(',', '', $sValue);
            return ((float) ($sValue));

        } else if (($cColon == 1) && ($cPoints == 1) && (strpos($sValue, '.') < strpos($sValue, ','))) {
            $sValue = str_replace('.', '', $sValue);
            $sValue = str_replace(',', '.', $sValue);

            return ((float) ($sValue));

        } else if (($cColon <= 1) && ($cPoints >= 1)) {
            $sValue = str_replace('.', '', $sValue);
            $sValue = str_replace(',', '.', $sValue);
            return ((float) ($sValue));

        } else if (($cColon >= 1) && ($cPoints <= 1)) {
            $sValue = str_replace(',', '.', $sValue);
            return ((float) ($sValue));

        } else {
            return null;
        }
    }

    public static function to_html($sString)
    {
        //$sString = htmlentities($sString,null,null,'ISO',true);
        //$sString = ereg_replace( "\xE2\x82\xAc", "&euro;", $sString );
        $sString = ereg_replace(chr(224), "&agrave;", $sString);
        $sString = ereg_replace(chr(225), "&aacute;", $sString);
        $sString = ereg_replace(chr(232), "&egrave;", $sString);
        $sString = ereg_replace(chr(233), "&eacute;", $sString);
        $sString = ereg_replace(chr(130), "&eacute;", $sString);
        $sString = ereg_replace(chr(235), "&euml;", $sString);
        $sString = ereg_replace(chr(236), "&igrave;", $sString);
        $sString = ereg_replace(chr(237), "&iacute;", $sString);
        $sString = ereg_replace(chr(242), "&ograve;", $sString);
        $sString = ereg_replace(chr(243), "&oacute;", $sString);
        $sString = ereg_replace(chr(249), "&ugrave;", $sString);
        $sString = ereg_replace(chr(250), "&uacute;", $sString);
        $sString = ereg_replace(chr(252), "&uuml;", $sString);
        $sString = ereg_replace(chr(195), "&eacute;", $sString);

        $sString = ereg_replace(chr(192), "&Agrave;", $sString);
        $sString = ereg_replace(chr(193), "&Aacute;", $sString);
        $sString = ereg_replace(chr(200), "&Egrave;", $sString);
        $sString = ereg_replace(chr(201), "&Eacute;", $sString);
        $sString = ereg_replace(chr(204), "&Igrave;", $sString);
        $sString = ereg_replace(chr(205), "&Iacute;", $sString);
        $sString = ereg_replace(chr(210), "&Ograve;", $sString);
        $sString = ereg_replace(chr(211), "&Oacute;", $sString);
        $sString = ereg_replace(chr(217), "&Ugrave;", $sString);
        $sString = ereg_replace(chr(218), "&Uacute;", $sString);
        $sString = ereg_replace(chr(220), "&Uuml;", $sString);

        $sString = ereg_replace(chr(209), "&Ntilde;", $sString);
        $sString = ereg_replace(chr(241), "&ntilde;", $sString);
        $sString = ereg_replace(chr(128), "&euro;", $sString);

        return ($sString);
    }

    /**
     * Converts HTML entities to normal chars
     *
     * @param string $sString
     * @return string
     */
    public static function from_html($sString)
    {
        $sString = ereg_replace("&agrave;", chr(224), $sString);
        $sString = ereg_replace("&aacute;", chr(225), $sString);
        $sString = ereg_replace("&egrave;", chr(232), $sString);
        $sString = ereg_replace("&eacute;", chr(233), $sString);
        $sString = ereg_replace("&igrave;", chr(236), $sString);
        $sString = ereg_replace("&iacute;", chr(237), $sString);
        $sString = ereg_replace("&ograve;", chr(242), $sString);
        $sString = ereg_replace("&oacute;", chr(243), $sString);
        $sString = ereg_replace("&ugrave;", chr(249), $sString);
        $sString = ereg_replace("&uacute;", chr(250), $sString);
        $sString = ereg_replace("&uuml;", chr(252), $sString);

        $sString = ereg_replace("&Agrave;", chr(192), $sString);
        $sString = ereg_replace("&Aacute;", chr(193), $sString);
        $sString = ereg_replace("&Egrave;", chr(200), $sString);
        $sString = ereg_replace("&Eacute;", chr(201), $sString);
        $sString = ereg_replace("&Igrave;", chr(204), $sString);
        $sString = ereg_replace("&Iacute;", chr(205), $sString);
        $sString = ereg_replace("&Ograve;", chr(210), $sString);
        $sString = ereg_replace("&Oacute;", chr(211), $sString);
        $sString = ereg_replace("&Ugrave;", chr(217), $sString);
        $sString = ereg_replace("&Uacute;", chr(218), $sString);
        $sString = ereg_replace("&Uuml;", chr(220), $sString);

        $sString = ereg_replace("&Ntilde;", chr(209), $sString);
        $sString = ereg_replace("&ntilde;", chr(241), $sString);
        $sString = ereg_replace("&euro;", "�", $sString);

        return ($sString);
    }

    public static function encodeTotalBarcode($dTotal)
    {

        $iPoint = strpos("$dTotal", ".");
        $iLength = strlen("$dTotal");
        $int = substr("$dTotal", 0, $iPoint);
        $int = str_pad($int, 5, 0, STR_PAD_LEFT);
        $dec = substr("$dTotal", $iPoint + 1, 2);
        $dec = str_pad($dec, 2, 0, STR_PAD_RIGHT);
        return $int . $dec;

    }

    public static function fillLanguages($iLanguage)
    {
        global $tpl;
        global $oController;
        $vLanguages = $oController->getLanguages();
        foreach ($vLanguages as $oLanguage) {
            $tpl->set_var("sLanguageName", Utils::to_html($oLanguage->getDescription()));
            $tpl->set_var("iLanguageId", $oLanguage->getId());
            $tpl->parse("LanguageItem", true);
        }
    }

    public static function fillCountries($iCountry)
    {
        global $tpl;
        global $oController;
        if (!$iCountry) {
            if (($_SESSION["isIndex"]) && ($_SESSION["isIndex"] == "1")) {
                $iCountry = $_COOKIE["iCountry"];
                $_SESSION["isIndex"] == "0";
            } else {
                $iCountry = $oController->getConfigValue("default_country");
            }
        }
        $vCountries = $oController->getCountries();
        foreach ($vCountries as $oCountry) {
            $tpl->set_var("sCountryName", Utils::to_html($oCountry->getDescription()));
            $tpl->set_var("iCountryId", $oCountry->getId());
            if ($iCountry == $oCountry->getId()) {
                $tpl->set_var("Selected", "selected='selected'");
            } else {
                $tpl->set_var("Selected", "");
            }
            $tpl->parse("CountryItem", true);
        }
    }

    public static function fillCategories($iCategory)
    {
        global $tpl;
        global $oController;
        global $oDictionary;

        $vCategories = $oController->getCategories();
        foreach ($vCategories as $oCategory) {
            if ($oCategory->getId() == $iCategory) {
                $sSelected = "selected='selected'";
            } else {
                $sSelected = "";
            }

            $tpl->set_var("sSelected", $sSelected);

            $tpl->set_var("sCategoryName", $oDictionary[$oCategory->getDictionaryLabel()]);
            $tpl->set_var("iCategoryId", $oCategory->getId());
            $tpl->parse("CategoryItem", true);
        }
    }

    public function writeLog($sFileName, $sValue)
    {

        $fp = fopen($sFileName, "a");
        $sValue = date("D M j G:i:s T Y") . " : " . $sValue . "\n";
        fputs($fp, $sValue, strlen($sValue));
        fclose($fp);

    }

    public static function currentPageURL()
    {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    public static function getURL()
    {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"];
        }
        return $pageURL;
    }

    public static function addDays($date, $iDays)
    {
        if (preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}/", $date)) {
            list($dia, $mes, $a�o) = split("/", $date);
        }

        if (preg_match("/[0-9]{1,2}-[0-9]{1,2}-([0-9][0-9]){1,2}/", $date)) {
            list($dia, $mes, $a�o) = split("-", $date);
        }

        $nueva = mktime(0, 0, 0, $mes, $dia, $a�o) + $iDays * 24 * 60 * 60;
        $nuevafecha = date("d-m-Y", $nueva);
        return ($nuevafecha);
    }

    /*

    public static function dateadd($date, $dd=0, $mm=0, $yy=0, $hh=0, $mn=0, $ss=0){

    $date_r = getdate(strtotime($date));
    $date_result = date("m/d/Y h:i:s", mktime(($date_r["hours"]+$hh),
    ($date_r["minutes"]+$mn),($date_r["seconds"]+$ss)($date_r["mon"]+$mm),($date_r["mday"]+$dd),($date_r["year"]+$yy)));

    return $date_result;

    }*/

    /**
     * Checks to see if a string is utf8 encoded.
     * @param string $str The string to be checked
     * @return bool True if $str fits a UTF-8 model, false otherwise.
     */
    public static function seems_utf8($str)
    {
        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0;
            }
            # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) {
                $n = 1;
            }
            # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) {
                $n = 2;
            }
            # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) {
                $n = 3;
            }
            # 11110bbb
            elseif (($c & 0xFC) == 0xF8) {
                $n = 4;
            }
            # 111110bb
            elseif (($c & 0xFE) == 0xFC) {
                $n = 5;
            }
            # 1111110b
            else {
                return false;
            }
            # Does not match any model
            for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                return false;
            }

            }
        }
        return true;
    }

    /**
     * Converts all accent characters to ASCII characters.
     * If there are no accent characters, then the string given is just returned.
     * @param string $string Text that might have accent characters
     * @return string Filtered string with replaced "nice" characters.
     */
    public static function remove_accents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if (self::seems_utf8($string)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
                chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
                chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
                chr(195) . chr(134) => 'AE', chr(195) . chr(135) => 'C',
                chr(195) . chr(136) => 'E', chr(195) . chr(137) => 'E',
                chr(195) . chr(138) => 'E', chr(195) . chr(139) => 'E',
                chr(195) . chr(140) => 'I', chr(195) . chr(141) => 'I',
                chr(195) . chr(142) => 'I', chr(195) . chr(143) => 'I',
                chr(195) . chr(144) => 'D', chr(195) . chr(145) => 'N',
                chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O',
                chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
                chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U',
                chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
                chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
                chr(195) . chr(158) => 'TH', chr(195) . chr(159) => 's',
                chr(195) . chr(160) => 'a', chr(195) . chr(161) => 'a',
                chr(195) . chr(162) => 'a', chr(195) . chr(163) => 'a',
                chr(195) . chr(164) => 'a', chr(195) . chr(165) => 'a',
                chr(195) . chr(166) => 'ae', chr(195) . chr(167) => 'c',
                chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
                chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
                chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
                chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
                chr(195) . chr(176) => 'd', chr(195) . chr(177) => 'n',
                chr(195) . chr(178) => 'o', chr(195) . chr(179) => 'o',
                chr(195) . chr(180) => 'o', chr(195) . chr(181) => 'o',
                chr(195) . chr(182) => 'o', chr(195) . chr(184) => 'o',
                chr(195) . chr(185) => 'u', chr(195) . chr(186) => 'u',
                chr(195) . chr(187) => 'u', chr(195) . chr(188) => 'u',
                chr(195) . chr(189) => 'y', chr(195) . chr(190) => 'th',
                chr(195) . chr(191) => 'y',
                // Decompositions for Latin Extended-A
                chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
                chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
                chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
                chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
                chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
                chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
                chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
                chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
                chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
                chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
                chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
                chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
                chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
                chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
                chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
                chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
                chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
                chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
                chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
                chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
                chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
                chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
                chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
                chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
                chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
                chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
                chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
                chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
                chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L',
                chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
                chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
                chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
                chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
                chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
                chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
                chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
                chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N',
                chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
                chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
                chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
                chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
                chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
                chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r',
                chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
                chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r',
                chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
                chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's',
                chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
                chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
                chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
                chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
                chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
                chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
                chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
                chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
                chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
                chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
                chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
                chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
                chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
                chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
                chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
                chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
                chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',
                // Decompositions for Latin Extended-B
                chr(200) . chr(152) => 'S', chr(200) . chr(153) => 's',
                chr(200) . chr(154) => 'T', chr(200) . chr(155) => 't',
                // Euro Sign
                chr(226) . chr(130) . chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194) . chr(163) => '');

            $string = strtr($string, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
            . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
            . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
            . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
            . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
            . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
            . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
            . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
            . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
            . chr(252) . chr(253) . chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

}

function replaceAcentos($sValue)
{
    $sValue = str_replace("�", "e", $sValue);
    $sValue = str_replace("�", "a", $sValue);
    $sValue = str_replace("�", "o", $sValue);
    $sValue = str_replace("�", "u", $sValue);
    $sValue = str_replace("�", "i", $sValue);
    $sValue = str_replace("�", "n", $sValue);
    return $sValue;
}

function utf_decode($sValue)
{
    $sValue = iconv("UTF-8", "CP1252", $sValue);
    return $sValue;

}

function showError($message, $code, $description = "")
{
    http_response_code($code);
    $error = new stdClass();
    $error->description = $description;
    $error->message = $message;
    echo json_encode($error);
    exit;
}

function reArrayFiles(&$file_post)
{
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);

    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }

    return $file_ary;
}

function logoutAndExit() {
    //remove PHPSESSID from browser
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), "", time() - 3600, "/");
    }

    //clear session from globals
    $_SESSION = array();

    //clear session from disk
    session_destroy();

    echo("NO_SESSION");
    http_response_code(400);

    exit();
}