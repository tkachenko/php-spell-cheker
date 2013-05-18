<?php
    /**
     * Check spell and correct text via proper api
     *
     * @param string $search
     */
    function checkSpell($query='', $lang = 'ru') {
      
              // switcher between API providers
              if($lang=='ru'||$lang=='ua'){
                  $fixed_query = yandexCheckSpell($query);
              }else{
                  $fixed_query = googleCheckSpell($query, $lang);
              }
      
              return $fixed_query;
          }

    /**
     * Check spell and correct text  via Yandex
     * !!! ALL INCORECT WORDS REMOVED !!!
     *
     *  http://api.yandex.ru/speller/doc/dg/reference/checkText.xml
     *
     * @param string $search
     */
    function yandexCheckSpell($query='') {
        $fixed_query = $query;
        $api_url = 'http://speller.yandex.net/services/spellservice.json/checkText?text='.urlencode($query);

        $json = @file_get_contents($api_url);

        if($json){
            $data = json_decode($json);

            foreach($data AS $w){

                if(isset($w->s[0])){
                    $replaceto = $w->s[0];
                }else{
                    $replaceto = '';
                }

                $fixed_query = str_replace($w->word, $replaceto, $fixed_query);
            }
        }
        return $fixed_query;
    }

    /**
     * Check spell and correct text via Google (NOT PUBLIC API)
     * @param string $search
     */
    function googleCheckSpell($query, $lang = 'ru', $ignoredigits=0, $ignoreallcaps=0){

        $api_url = "https://www.google.com/tbproxy/spell?lang=".$lang;

        $fixed_query = $query;

        $body = '<?xml version="1.0" encoding="utf-8" ?>';
        $body .= '<spellrequest textalreadyclipped="0" ignoredubs="1" ignoredigits="'.$ignoredigits.'" ignoreallcaps="'.$ignoreallcaps.'">';
        $body .= "<text>".urldecode($query)."</text>";
        $body .= '</spellrequest>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $output = curl_exec($ch);
        curl_close($ch);
        
        preg_match_all('|<c o="(.*)" l="(.*)" s="(.*)">(.*)</c>|Uis', $output, $data);

        foreach($data[4] as $key=>$value) {
            if(trim($value))
                $fixed_query = str_replace(substr($query, $data[1][$key], $data[2][$key]), $value, $fixed_query);

        }

        return $fixed_query;
    }
