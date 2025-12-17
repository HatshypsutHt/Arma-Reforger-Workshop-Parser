<?php
$result = [];
$error  = '';
$tmp_file = __DIR__ . '/url.txt';

function load_html($url)
{
	$context = stream_context_create([
		'http' => [
			'method'  => 'GET',
			'header'  => "User-Agent: Mozilla/5.0\r\n",
			'timeout' => 10
		]
	]);

	return @file_get_contents($url, false, $context);
}

function parse_mod_data($html)
{
	libxml_use_internal_errors(true);
	$dom = new DOMDocument();
	$dom->loadHTML($html);
	$xpath = new DOMXPath($dom);

	$nameNode    = $xpath->query("//h1[contains(@class,'text-3xl')]")->item(0);
	$versionNode = $xpath->query("//dt[text()='Version']/following-sibling::dd[1]")->item(0);
	$idNode      = $xpath->query("//dt[text()='ID']/following-sibling::dd[1]//span")->item(0);

	if (!$nameNode || !$versionNode || !$idNode) {
		return null;
	}

	return [
		'modId'   => trim($idNode->textContent),
		'name'    => trim($nameNode->textContent),
		'version' => trim($versionNode->textContent)
	];
}

function parse_dependencies($html)
{
	libxml_use_internal_errors(true);
	$dom = new DOMDocument();
	$dom->loadHTML($html);
	$xpath = new DOMXPath($dom);

	$deps = [];

	$nodes = $xpath->query("//section[contains(@class,'py-8')]//h2[text()='Dependencies']/following-sibling::div//a");

	foreach ($nodes as $a) {
		$href = $a->getAttribute('href');
		if ($href && strpos($href, '/workshop/') === 0) {
			$deps[] = 'https://reforger.armaplatform.com' . $href;
		}
	}

	return array_unique($deps);
}

if (!empty($_POST['page_url'])) {

	$url = trim($_POST['page_url']);

	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		$error = 'Invalid URL.';
	}
	elseif (strpos($url, 'https://reforger.armaplatform.com/workshop/') !== 0) {
		$error = 'URL must start with https://reforger.armaplatform.com/workshop/';
	}
	else {

		$html = load_html($url);

		if ($html === false) {
			$error = 'Failed to load the page.';
		}
		else {

			$main_mod = parse_mod_data($html);
			if (!$main_mod) {
				$error = 'Failed to parse mod data.';
			}
			else {

				$result[] = $main_mod;
				$deps = parse_dependencies($html);

				if (!empty($deps)) {

					file_put_contents($tmp_file, implode(PHP_EOL, $deps));
					$urls = file($tmp_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

					foreach ($urls as $dep_url) {
						$dep_html = load_html($dep_url);
						if ($dep_html) {
							$dep_mod = parse_mod_data($dep_html);
							if ($dep_mod) {
								$result[] = $dep_mod;
							}
						}
					}

					unlink($tmp_file);
				}
			}
		}
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Arma Reforger Workshop Parser</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

	<style>
		body { background:#0f0f0f; color:#e0e0e0; }
		h4 { color:#5caf90; }
		.card { background:#1a1a1a; border:1px solid #2a2a2a; }
		.form-control { background:#121212; color:#fff; border:1px solid #333; }
		.form-control:focus { border-color:#5caf90; box-shadow:none; }
		.btn-primary { background:#5caf90; border:none; }
		.result-box {
			background:#0b0b0b;
			border:1px dashed #3a3a3a;
			padding:15px;
			font-family:monospace;
			color:#e8e8e8;
			white-space:pre;
		}
	</style>
</head>
<body>

<div class="container py-5">
	<div class="row justify-content-center">
		<div class="col-lg-9">

			<div class="card mb-4">
				<div class="card-body">
					<h4 class="mb-3">Arma Reforger Workshop Parser</h4>
					<form method="post">
						<input type="url" name="page_url" class="form-control mb-3"
							   placeholder="https://reforger.armaplatform.com/workshop/XXXXXXXX"
							   required>
						<button class="btn btn-primary w-100">Process page</button>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h4 class="mb-3">Result</h4>

					<div class="result-box" id="jsonOutput">
<?php
if ($error) {
	echo $error;
}
elseif (!empty($result)) {
	$last = count($result) - 1;
	foreach ($result as $i => $mod) {
		echo json_encode($mod, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		if ($i !== $last) echo ",\n";
	}
}
else {
	echo 'Enter an Arma Reforger Workshop URL to begin.';
}
?>
					</div>

					<button id="copyJsonBtn" class="btn btn-outline-light mt-3 w-100" style="display:none">
						Copy code
					</button>
				</div>
			</div>

		</div>
	</div>
</div>

<script>
(function () {
	const btn = document.getElementById('copyJsonBtn');
	const out = document.getElementById('jsonOutput');

	if (!btn || !out) return;

	const text = out.textContent.trim();
	if (text && text.startsWith('{')) {
		btn.style.display = 'block';
	}

	btn.addEventListener('click', () => {
		const value = out.textContent.trim();
		navigator.clipboard.writeText(value).then(() => {
			btn.textContent = 'Copied ✔';
			setTimeout(() => btn.textContent = 'Copy code', 1500);
		});
	});
})();
</script>

</body>
</html>
