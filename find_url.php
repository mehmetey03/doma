<?php
function check_url_validity($url, $multi_curl_handle) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0.5);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

    curl_multi_add_handle($multi_curl_handle, $ch);
    return $ch;
}

function find_dynamic_url() {
    $base_url = "https://izlemac";
    $valid_urls = [];
    $multi_curl_handle = curl_multi_init();
    $curl_handles = [];
    $max_parallel_requests = 500;
    $counter = 0;

    for ($i = 0; $i <= 9999; $i++) {
        $dynamic_part = str_pad($i, 4, "0", STR_PAD_LEFT);
        $url = $base_url . $dynamic_part . ".sbs";

        if ($counter < $max_parallel_requests) {
            $ch = check_url_validity($url, $multi_curl_handle);
            $curl_handles[] = $ch;
            $counter++;
        } else {
            do {
                curl_multi_exec($multi_curl_handle, $active);
                curl_multi_select($multi_curl_handle);
            } while ($active > 0);

            foreach ($curl_handles as $ch) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 200) {
                    $valid_urls[] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                }
                curl_multi_remove_handle($multi_curl_handle, $ch);
                curl_close($ch);
            }

            $ch = check_url_validity($url, $multi_curl_handle);
            $curl_handles = [$ch];
            $counter = 1;
        }

        if (!empty($valid_urls)) break;
    }

    if (!empty($curl_handles)) {
        do {
            curl_multi_exec($multi_curl_handle, $active);
            curl_multi_select($multi_curl_handle);
        } while ($active > 0);

        foreach ($curl_handles as $ch) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == 200) {
                $valid_urls[] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            }
            curl_multi_remove_handle($multi_curl_handle, $ch);
            curl_close($ch);
        }
    }

    curl_multi_close($multi_curl_handle);
    return $valid_urls ? $valid_urls[0] : null;
}

$valid_url = find_dynamic_url();

if ($valid_url) {
    file_put_contents(__DIR__ . "/valid_url.txt", $valid_url);
    echo "Geçerli URL bulundu ve kaydedildi: " . $valid_url . "\n";
} else {
    echo "Geçerli URL bulunamadı.\n";
}
