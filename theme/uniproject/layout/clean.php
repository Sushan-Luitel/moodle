<?php
// theme/uniproject/layout/clean.php - NO NAVIGATION BAR
echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body <?php echo $OUTPUT->body_attributes(); ?>>
    <?php echo $OUTPUT->standard_top_of_body_html() ?>
    
    <!-- MAIN CONTENT ONLY - NO HEADER, NO NAVIGATION, NO FOOTER -->
    <div id="page">
        <div id="page-content">
            <?php echo $OUTPUT->main_content(); ?>
        </div>
    </div>
    
    <?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>