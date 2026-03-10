<?php
$key="eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJ7XCJpZFwiOjQ0Nzg1LFwiZmlkXCI6MTAwMTI3NzgzMjEwLFwiY3JlYXRlZEF0XCI6XCIyMDI1LTA3LTA3VDA2OjEyOjAzLjQ3NTQ1MVpcIixcImF2YWlsYWJsZVRvXCI6bnVsbH0ifQ.hG8W4_Zslp1_JcbrCyuyAg4M-vpqUhHuFMGEcgXwOltsjoOCdbXyKta0S9Z7cNs2_S--W2aGniTTYxuUug71YA";
$data = json_decode(file_get_contents('php://input'), true);
//$type = $data['type']; // 'inn' или 'ogrnip'
//$value = preg_replace('/\D/', '', $data['value']);
$type="inn";
$value = "1101460856";
$url = "https://lknpd.nalog.ru/api/v1/services/ident/by_inn?inn=$value";
$opts = ["http"=>["method"=>"GET","header"=>"Accept: application/json\r\n"]];
$res = @file_get_contents($url, false, stream_context_create($opts));
echo $res ?: json_encode(['error'=>'fail']);
?>