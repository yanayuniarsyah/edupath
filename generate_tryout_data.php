<?php
$transcriptPath = 'C:\\Users\\yanay\\.gemini\\antigravity-ide\\brain\\51ed2b76-3bbc-4ff0-88a1-d812597226c2\\.system_generated\\logs\\transcript_full.jsonl';
$lines = file($transcriptPath);
$all = [];
foreach($lines as $l){
    $d = json_decode($l, true);
    if($d && $d['type'] === 'USER_INPUT' && strpos($d['content'] ?? '', 'EDUPATH — TRYOUT UTBK') !== false) {
        $all[] = $d['content'];
    }
}
file_put_contents('tryout_raw_text.txt', implode("\n===\n", $all));
echo count($all) . " prompts extracted.\n";
