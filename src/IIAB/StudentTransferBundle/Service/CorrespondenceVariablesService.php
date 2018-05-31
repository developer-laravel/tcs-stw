<?php
/**
 * Company: Image In A Box
 * Date: 2/2/15
 * Time: 12:00 PM
 * Copyright: 2015
 */

namespace IIAB\StudentTransferBundle\Service;

class CorrespondenceVariablesService {

	function __construct() {
	}

	/**
	 * Returns an array of twig entities for use in templates.
	 *
	 * @return array
	 */
	static function getDynamicVariables() {

        return [
            'Submission First Name' => '{{ submission.firstName }}',
            'Submission Last Name' => '{{ submission.lastName }}',
            'Submission Form ID' => '{{ submission.formID }}',
            'Submission Confirmation Number' => '{{ submission.confirmationNumber }}',
            'Submission Email Address' => '{{ submission.email }}',
            'Enrollment School Year' => '{{ submission.enrollmentPeriod }}',
            'Awarded School Name' => '{{ submission.awardedSchoolID.formattedString }}',
            'Accept Online Deadline Date' => '{{ acceptOnlineDate }}',
            'Accept Online Deadline Time' => '{{ acceptOnlineTime }}',
            'Accept Offline Deadline Date' => '{{ acceptOfflineDate }}',
            'Accept Offline Deadline Time' => '{{ acceptOfflineTime }}',
            'Accept URL' => "{{ url( 'stw_lottery_accept' , { uniqueID: submission.url } ) }}",
            'Waitlisted School Name' => '{{ waitListedSchool.formattedString }}',
            'Waitlist Expiration Date' => '{{ submission.enrollmentPeriod.afterLotteryEndingDate|date("Y") }}'
        ];
	}

    /**
     * Returns an array of twig templates
     *
     * @param string twig template
     *
     * @return array
     */
    static function divideEmailBlocks($template) {
        $emailTemplates = [];
        $matches = [];
        $regex = [
            'subject'   => '/{% block subject %}(.*){% endblock subject %}/s',
            'body_text' => '/{% block body_text %}(.*){% endblock body_text %}/s',
            'body_html' => '/{% block body_html %}(.*){% endblock body_html %}/s',
        ];

        foreach($regex as $key => $expression){
            preg_match($expression, $template, $matches);

            $emailTemplates[$key] = ($key == 'body_text') ? nl2br( $matches[1] ) : $matches[1];
        }
        return $emailTemplates;
    }

    /**
     * Returns an twig template
     *
     * @param array( subject, body_text, body_html ) twig templates
     *
     * @return string twig template
     */
    static function combineEmailBlocks($templates) {
        $templates['subject'] = html_entity_decode( $templates['subject'], ENT_QUOTES );

        $matches = [];
        $bodyText = ( isset( $templates['body_text'] ) ) ? $templates['body_text'] : $templates['body_html'];
        $bodyText = ( preg_match( '/<!-- BODY -->(.*)<!-- \/BODY -->/s', $bodyText, $matches ) ) ? $matches[1] : $bodyText;
        $bodyText = str_replace( ["<i>", "</i>"] , ["_", "_"], $bodyText );
        $bodyText = str_replace( "<a>", "|a", $bodyText );
        $bodyText = str_replace( "<li", " - <li", $bodyText);
        $bodyText = str_replace( "|raw ", "|raw|striptags ", $bodyText);
        $bodyText = strip_tags( $bodyText );
        $bodyText = html_entity_decode( $bodyText, ENT_QUOTES );
        $bodyText = trim( $bodyText );
        $bodyText = preg_replace( '/\n\s+/', PHP_EOL.PHP_EOL, $bodyText );

        return  '{% block subject %}'.
                strip_tags($templates['subject']) .
                '{% endblock subject %}{% block body_text %}'.
                //strip_tags( str_replace("<br />", PHP_EOL, $templates['body_html'] ) ) .
                $bodyText.
                '{% endblock body_text %}{% block body_html %}'.
                $templates['body_html'].
                '{% endblock body_html %}';
    }

     /**
     * Returns an array of twig templates
     *
     * @param string twig template
     *
     * @return array
     */
    static function dividePageBlocks($template) {
        $pageTemplates = [];
        $matches = [];
        $regex = [
            'pageTitle'   => '/{% block pageTitle %}(.*){% endblock pageTitle %}/s',
            'body' => '/{% block body %}(.*){% endblock body %}/s',
        ];

        foreach($regex as $key => $expression){
            preg_match($expression, $template, $matches);

            $pageTemplates[$key] =  $matches[1];
        }
        return $pageTemplates;
    }

}