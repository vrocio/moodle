<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains a custom renderer class used by the forum module.
 *
 * @package mod-forum
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A custom renderer class that extends the moodle_renderer_base and
 * is used by the forum module.
 * 
 * @package mod-forum
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class moodle_mod_forum_renderer extends moodle_renderer_base {

    /**
     * A reference to the current general renderer probably {@see moodle_core_renderer}
     * @var moodle_renderer_base
     */
    protected $output;

    /**
     * Contructor method, calls the parent constructor
     * @param moodle_page $page
     * @param moodle_renderer_base $output Probably moodle_core_renderer
     */
    public function __construct($page, $output) {
        parent::__construct($page);
        $this->output = $output;
    }

    /**
     * This method is used to generate HTML for a subscriber selection form that
     * uses two user_selector controls
     *
     * @param user_selector_base $existinguc
     * @param user_selector_base $potentialuc
     * @return string
     */
    public function subscriber_selection_form(user_selector_base $existinguc, user_selector_base $potentialuc) {
        $output = '';
        $formattributes = array();
        $formattributes['id'] = 'subscriberform';
        $formattributes['action'] = '';
        $formattributes['method'] = 'post';
        $output .= $this->output_start_tag('form', $formattributes);
        $output .= $this->output_empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));

        $existingcell = new html_table_cell();
        $existingcell->text = $existinguc->display(true);
        $existingcell->set_classes(array('existing'));
        $actioncell = new html_table_cell();
        $actioncell->text  = $this->output_start_tag('div', array());
        $actioncell->text .= $this->output_empty_tag('input', array('type'=>'submit', 'name'=>'subscribe', 'value'=>$this->page->theme->larrow.' '.get_string('add'), 'class'=>'actionbutton'));
        $actioncell->text .= $this->output_empty_tag('br', array());
        $actioncell->text .= $this->output_empty_tag('input', array('type'=>'submit', 'name'=>'unsubscribe', 'value'=>$this->page->theme->rarrow.' '.get_string('remove'), 'class'=>'actionbutton'));
        $actioncell->text .= $this->output_end_tag('div', array());
        $actioncell->set_classes(array('actions'));
        $potentialcell = new html_table_cell();
        $potentialcell->text = $potentialuc->display(true);
        $potentialcell->set_classes(array('potential'));

        $table = new html_table();
        $table->set_classes(array('subscribertable','boxaligncenter'));
        $table->data = array(html_table_row::make(array($existingcell, $actioncell, $potentialcell)));
        $output .= $this->output->table($table);
        
        $output .= $this->output_end_tag('form');
        return $output;
    }

    /**
     * This function generates HTML to display a subscriber overview, primarily used on
     * the subscribers page if editing was turned off
     *
     * @param array $users
     * @param object $forum
     * @param object $course
     * @return string
     */
    public function subscriber_overview($users, $forum , $course) {
        $output = '';
        if (!$users || !is_array($users) || count($users)===0) {
            $output .= $this->output->heading(get_string("nosubscribers", "forum"));
        } else {
            $output .= $this->output->heading(get_string("subscribersto","forum", "'".format_string($forum->name)."'"));
            $table = new html_table();
            $table->cellpadding = 5;
            $table->cellspacing = 5;
            $table->tablealign = 'center';
            $table->data = array();
            foreach ($users as $user) {
                $table->data[] = array($this->output->user_picture(moodle_user_picture::make($user, $course->id)), fullname($user), $user->email);
            }
            $output .= $this->output->table($table);
        }
        return $output;
    }

    /**
     * This is used to display a control containing all of the subscribed users so that
     * it can be searched
     *
     * @param user_selector_base $existingusers
     * @return string
     */
    public function subscribed_users(user_selector_base $existingusers) {
        $output  = $this->output->box_start('subscriberdiv boxaligncenter');
        $output .= $this->output_tag('p', array(), get_string('forcessubscribe', 'forum'));
        $output .= $existingusers->display(true);
        $output .= $this->output->box_end();
        return $output;
    }
        

}