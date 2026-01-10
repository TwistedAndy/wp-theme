<?php

/**
 * Include the microdata
 */
function tw_breadcrumbs_inject(): void
{
	$schemas = tw_app_get('microdata');

	if ($schemas) {
		foreach ($schemas as $schema) {
			echo "<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n</script>\n";
		}
	}
}

add_action('wp_footer', 'tw_breadcrumbs_inject', 20);


/**
 * Add the microdata
 *
 * @param array $schema
 *
 * @return void
 */
function tw_microdata_add(array $schema): void
{
	$schemas = tw_app_get('microdata');

	if (!is_array($schemas)) {
		$schemas = [];
	}

	if (empty($schema['@type'])) {
		return;
	}

	$type = $schema['@type'];

	if (!empty($schemas[$type])) {

		if ($type == 'FAQPage' and !empty($schema['mainEntity'])) {
			$schema['mainEntity'] = array_merge($schemas[$type]['mainEntity'], $schema['mainEntity']);
		}

	}

	$schemas[$type] = $schema;

	tw_app_set('microdata', $schemas);
}