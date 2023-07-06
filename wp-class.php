<?php
//author Mertcan K.
class wp_comment {

    private static function basic_cURL($address) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $address);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $redirect_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $cikti = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array("response" => $cikti, "http_status" => $http_status,"redirect" => $redirect_url);
    }

    public static function sendComment($postaddress, $variables = array()) {
    $json = file_get_contents("activitiy.json");
    $json = json_decode($json, true);

    $gir = self::basic_cURL($postaddress);
    foreach($json as $key => $entry){
        if ($entry['comment_address'] == $gir["redirect"]) {
            exit("Bu siteye gonderdik!");
        }
    }
    
    
    preg_match_all("@name=\"comment_post_ID\" value=\"(.*?)\"@si", $gir["response"], $post_id);
    @$post_id = $post_id[1][0];
    
    if(empty(trim($post_id))) {
       preg_match_all("@name='comment_post_ID' value='(.*?)'@si", $gir["response"], $post_id);
       @$post_id = $post_id[1][0];
        
    }

    preg_match_all("@name=\"akismet_comment_nonce\" value=\"(.*?)\"@si", $gir["response"], $akismet);
    $akismet = $akismet[1][0];

    preg_match_all("@action=\"(.*?)\"@si", $gir["response"], $post_adress);

    
    if (strstr($post_adress[1][0], 'wp-comments-post.php')) {
        $postaddress_curl = $post_adress[1][0];
    }elseif(strstr($post_adress[1][1], 'wp-comments-post.php')) {
        $postaddress_curl = $post_adress[1][1];
    }elseif(strstr($post_adress[1][2], 'wp-comments-post.php')) {
        $postaddress_curl = $post_adress[1][2];
    }else{
        $postaddress_curl = 0;
    }
    
    
        if($postaddress_curl==false) {
        array_push($json,["status" => 0,"comment_address" => htmlspecialchars($gir["redirect"]),"message" => parse_url($postaddress)["host"]." could not find wp-comments-post.php of address"]);
        $json = json_encode($json);
        file_put_contents("activitiy.json",$json);
        return [
            "status" => 0,
            "comment_address" => htmlspecialchars($gir["redirect"]),
            "message" => parse_url($postaddress)["host"]." could not find wp-comments-post.php of address"
        ];
        


        } else {
        if(empty(trim($post_id))) {
        array_push($json,["status" => 0,"comment_address" => htmlspecialchars($gir["redirect"]),"message" => parse_url($postaddress)["host"]." could not find post_id data of address"]);
        $json = json_encode($json);
        file_put_contents("activitiy.json",$json);
        return [
            "status" => 0,
            "comment_address" => htmlspecialchars($gir["redirect"]),
            "message" => parse_url($postaddress)["host"]." could not find post_id data of address"
        ];
        
        } else {
        $postfields = [];
        $postfields["comment"] = $variables["comment"];
        $postfields["author"] = $variables["author"];
        $postfields["email"] = $variables["email"];
        $postfields["url"] = $variables["site_address"];
        $postfields["comment_post_ID"] = $post_id;
        $postfields["comment_parent"] = 0;
        $postfields["wp-comment-cookies-consent"] = "yes";

        if(!empty(trim($akismet))) {
            $postfields["akismet_comment_nonce"] = $akismet;
            $postfields["ak_hp_textarea"] = "";
            $postfields["ak_js"] = time();
        }

        
        $ch = curl_init($postaddress_curl);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36");
        curl_setopt($ch, CURLOPT_REFERER, $postaddress);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        $index = curl_exec($ch);
        
        if(strstr($index, "error-page")) {
            array_push($json,["status" => 0,"comment_address" => htmlspecialchars($gir["redirect"]),"message" => "You have posted the same comment before or have been blocked by this site. Failed to submit comment"]);
        $json = json_encode($json);
        file_put_contents("activitiy.json",$json);
        return [
            "status" => 0,
            "comment_address" => htmlspecialchars($gir["redirect"]),
            "message" => "You have posted the same comment before or have been blocked by this site. Failed to submit comment"
        ];
        
        } else {
        if(strstr($index, "comment-awaiting-moderation")) {
            array_push($json,["status" => 0,"comment_address" => htmlspecialchars($gir["redirect"]),"message" => "The comment probably fell into the approval process"]);
        $json = json_encode($json);
        file_put_contents("activitiy.json",$json);
        return [
            "status" => 1,
            "comment_address" => htmlspecialchars($gir["redirect"]),
            "message" => "The comment probably fell into the approval process"
        ];
        
        } else {
        array_push($json,["status" => 0,"comment_address" => htmlspecialchars($gir["redirect"]),"message" => "Comment posted, check it out"]);
        $json = json_encode($json);
        file_put_contents("activitiy.json",$json);
        return [
            "status" => 1,
            "comment_address" => htmlspecialchars($gir["redirect"]),
            "message" => "Comment posted, check it out"
        ];
        
        }

        }
    }
    }
    }


}
