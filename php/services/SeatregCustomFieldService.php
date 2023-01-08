<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCustomFieldService {
   /**
     *
     * Return custom fields HTML markup
     *
    */
    public static function generateCustomFieldsMarkup($customFields) {

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
                                        <select>
                                            <?php foreach($customField['options'] as $option) : ?>
                                                <option><span><?php esc_html_e($option); ?></span></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                           <?php 
                        }else if( $customField['type'] == 'text' ){
                            ?>
                                <div class="custom-field" data-type="text">
                                    <label><span><?php esc_html_e($customField['label']); ?></span><input type="text" /></label>
                                </div>
                            <?php
                        }else if( $customField['type'] == 'check' ) {
                            ?>
                                <div class="custom-field" data-type="check">
                                    <label><span><?php esc_html_e($customField['label']); ?></span><input type="checkbox" /></label>
                                </div>
                            <?php
                        }
                    } 
                ?>
            </div>
        <?php
    }
}