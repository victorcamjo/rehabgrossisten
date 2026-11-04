
<?php

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

?>

<div class="fusion-portfolio-archive parent-portfolio-archive">
    <div class="post-content">
        <p>
            Den här guiden ger dig vägledning och information om vanligt förekommande skador som i många fall kan lindras
            genom avlastande eller fixerande ortosbehandling. Prata med din fysioterapeut eller annan behandlande sjukvårspersonal
            för mer information om vad som skulle kunna hjälpa dig. Du kan även kontakta oss så kan vi guida dig vidare.
        </p>


        <div class="category-container">
            <?php
                echo do_shortcode('[cat_card category="nacke" title="Nacke" term="skadeguiden-kategori"]');
                echo do_shortcode('[cat_card category="axel" title="Axel" term="skadeguiden-kategori"]');
                echo do_shortcode('[cat_card category="arm" title="Arm" term="skadeguiden-kategori"]');
                echo do_shortcode('[cat_card category="handled-tumme" title="Hand" term="skadeguiden-kategori"]');
                echo do_shortcode('[cat_card category="rygg-hoft" title="Rygg" term="skadeguiden-kategori"]');
                echo do_shortcode('[cat_card category="ben-kna" title="Ben" term="skadeguiden-kategori"]');
                echo do_shortcode('[cat_card category="fot-vrist" title="Fot" term="skadeguiden-kategori"]');
            ?>
        </div>
    </div>
</div>