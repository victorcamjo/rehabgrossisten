<?php

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

global $product;

if ( $product )
{
    if( is_product($product) ) :
        
        $queried_object = get_queried_object();
        $img_url = get_field('storleksguide', $queried_object->ID);        
        $table = get_field('storleksguide_table', $queried_object->ID);
        $skadeguiden_text = get_field('storleksguide_text', $queried_object->ID);

        if ($img_url || $table || $skadeguiden_text) : ?>
            <div id="storleksguiden" class="modal-window" style="opacity: 0; visibility: hidden;">
                <div class="modal-content">
                    <a href="#close" title="Stäng" class="modal-close">Stäng</a>
                    <div class="modal-title">Storleksguide</div>                    
                    <div class="modal-body">
                        
                        <?php if ($table) : ?>
                            <table class="table-1 storleksguide-table">
                                <thead>
                                    <tr>
                                    <?php foreach($table['header'] as $header) : ?>
                                        <th><?php echo $header['c']; ?></th>
                                    <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($table['body'] as $row) : ?>
                                        <tr>
                                            <?php foreach($row as $column) : ?>
                                                <td><?php echo $column['c']; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <?php if ($skadeguiden_text || $img_url) :?>
                            <div class="storleksguide-modal-body">
                                <?php 
                                if ($img_url) :?>
                                    <div class="storleksguide-modal-body-image">
                                        <img src="<?php echo $img_url; ?>" alt="Storleksguiden" />
                                    </div>
                                <?php 
                                endif;

                                if($skadeguiden_text) {
                                    echo $skadeguiden_text;
                                } ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif;
    endif;
    ?>
<?php } ?>


<div class="loader-container">
    <div class="loader"></div>
</div>

<div class="spinner hide">
    <div class="bounce1"></div>
    <div class="bounce2"></div>
    <div class="bounce3"></div>
</div>

<div class="hidden-print-div"></div>

<div id="hr-recom-k6655dfb8f72036773fb25150"></div>