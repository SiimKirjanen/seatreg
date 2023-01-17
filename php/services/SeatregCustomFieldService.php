<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCustomFieldService {
   /**
     *
     * Return custom fields HTML markup
     * * @param array $customFields user created custom fields
     * * @param boolean $addButtons adding add button
     *
    */
    public static function generateCustomFieldsMarkup($customFields, $addButtons) {

        if(!$customFields) {
            return "";
        }

        ?>
            <div class="custom-fields">
                <?php 
                    foreach( $customFields as $customField ) {
                        if( $customField['type'] == 'sel' ) {
                            ?>
                                <div class="custom-field" data-type="sel">
                                    <label><span><?php esc_html_e($customField['label']); ?></span>
                                        <select name="<?php esc_html_e($customField['label']); ?>">
                                            <?php foreach($customField['options'] as $option) : ?>
                                                <option><span><?php esc_html_e($option); ?></span></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <?php if($addButtons): ?>
                                        <i class="fa fa-plus-circle fa-lg" data-action="remove" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                           <?php 
                        }else if( $customField['type'] == 'text' ){
                            ?>
                                <div class="custom-field" data-type="text">
                                    <label><span><?php esc_html_e($customField['label']); ?></span><input type="text" name="<?php esc_html_e($customField['label']); ?>" /></label>
                                    <?php if($addButtons): ?>
                                        <i class="fa fa-plus-circle fa-lg" data-action="remove" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                            <?php
                        }else if( $customField['type'] == 'check' ) {
                            ?>
                                <div class="custom-field" data-type="check">
                                    <label><span><?php esc_html_e($customField['label']); ?></span><input type="checkbox" name="<?php esc_html_e($customField['label']); ?>" value="1" checked /></label>
                                    <?php if($addButtons): ?>
                                        <i class="fa fa-plus-circle fa-lg" data-action="remove" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                            <?php
                        }
                    } 
                ?>
            </div>
        <?php
    }
}