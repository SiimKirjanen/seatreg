<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregCustomFieldService {
    //Also what the translation plugin's string names start with
    const LABEL_STRING_PREFIX = 'Custom field: ';
    const OPTION_STRING_PREFIX = 'Custom field option: ';

    private static $translatedText = array();

    /**
     *
     * Names a label after the text itself, not after the registration, so the same label written in
     * several registrations is one string a translator answers once.
     *
     */
    public static function labelStringName($label) {
        return self::LABEL_STRING_PREFIX . $label;
    }

    /**
     *
     * The option carries its label too, as two fields can offer the same choice while meaning
     * different things by it.
     *
     */
    public static function optionStringName($label, $option) {
        return self::OPTION_STRING_PREFIX . $label . ' - ' . $option;
    }

    /**
     *
     * The label in the current language, or the admin's own text when there is no translation.
     * Display only: the stored label stays what the admin typed, as everything from the submitted
     * booking data to the export filter matches on it.
     *
     * @param string $label the label the admin entered
     *
     * @return string
     *
     */
    public static function translateLabel($label) {
        $translated = self::translated($label, self::labelStringName($label));

        if( !SeatregDataValidation::validateCustomFieldLabel($translated)->valid ) {
            $translated = $label;
        }

        /**
         * Filters the label a custom field is shown with, after any string translation.
         *
         * @param string $translated the label to display
         * @param string $label the label the admin entered, which stays the stored one
         */
        return self::stringOr( apply_filters(SEATREG_FILTER_CUSTOM_FIELD_LABEL, $translated, $label), $translated );
    }

    /**
     *
     * One choice of a select field in the current language. Display only, for the same reason a
     * label is: a submitted value has to be one of the options as the admin wrote them.
     *
     * @param string $label the label of the field the option belongs to
     * @param string $option the option the admin entered
     *
     * @return string
     *
     */
    public static function translateOption($label, $option) {
        $translated = self::translated($option, self::optionStringName($label, $option));

        /**
         * Filters the text a select option is shown with, after any string translation.
         *
         * @param string $translated the option text to display
         * @param string $option the option the admin entered, which stays the stored one
         * @param string $label the label of the field the option belongs to
         */
        return self::stringOr( apply_filters(SEATREG_FILTER_CUSTOM_FIELD_OPTION, $translated, $option, $label), $translated );
    }

    /**
     *
     * The custom fields of a registration with the text to display added beside the text to store,
     * for the booking page to render one and send back the other.
     *
     * @param string $customFieldsJson the custom_fields column
     *
     * @return array|null null when the registration has no custom fields
     *
     */
    public static function translateDefinitions($customFieldsJson) {
        $customFields = json_decode($customFieldsJson);

        if( !is_array($customFields) || count($customFields) === 0 ) {
            return null;
        }

        foreach($customFields as $customField) {
            if( !isset($customField->label) || !is_string($customField->label) ) {
                continue;
            }

            $customField->labelTranslated = self::translateLabel($customField->label);

            if( isset($customField->options) && is_array($customField->options) ) {
                $customField->optionsTranslated = array_map(function($option) use($customField) {
                    return is_string($option) ? self::translateOption($customField->label, $option) : $option;
                }, $customField->options);
            }
        }

        return $customFields;
    }

    /**
     *
     * A booking table asks for the same label once per booking, and the booking page for every
     * option of every field, so the lookup is answered from memory after the first time.
     *
     */
    private static function translated($value, $stringName) {
        $key = $stringName . '|' . determine_locale();

        if( !isset(self::$translatedText[$key]) ) {
            $translated = trim( (string) SeatregStringTranslationService::translate($value, $stringName) );
            self::$translatedText[$key] = $translated === '' ? $value : $translated;
        }

        return self::$translatedText[$key];
    }

    private static function stringOr($filtered, $fallback) {
        return is_string($filtered) && trim($filtered) !== '' ? $filtered : $fallback;
    }

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
                                    <label><span><?php echo esc_html($customField['label']); ?></span>
                                        <select name="<?php echo esc_attr($customField['label']); ?>">
                                            <?php foreach($customField['options'] as $option) : ?>
                                                <option><span><?php echo esc_html($option); ?></span></option>
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
                                    <label><span><?php echo esc_html($customField['label']); ?></span><input type="text" name="<?php echo esc_attr($customField['label']); ?>" /></label>
                                    <?php if($addButtons): ?>
                                        <i class="fa fa-plus-circle fa-lg" data-action="remove" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                            <?php
                        }else if( $customField['type'] == 'check' ) {
                            ?>
                                <div class="custom-field" data-type="check">
                                    <label><span><?php echo esc_html($customField['label']); ?></span><input type="checkbox" name="<?php echo esc_attr($customField['label']); ?>" value="1" checked /></label>
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