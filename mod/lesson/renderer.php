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
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package   lesson
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

class moodle_mod_lesson_renderer extends moodle_renderer_base {

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
     * Magic method used to pass calls otherwise meant for the standard renderer
     * to it to ensure we don't go causing unnessecary greif.
     * 
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments) {
        if (method_exists($this->output, $method)) {
            return call_user_func_array(array($this->output, $method), $arguments);
        } else {
            throw new coding_exception('Unknown method called against '.__CLASS__.' :: '.$method);
        }
    }

    /**
     * Returns the header for the lesson module
     *
     * @param lesson $lesson
     * @param string $currenttab
     * @param bool $extraeditbuttons
     * @param int $lessonpageid
     * @return string
     */
    public function header($lesson, $currenttab = '', $extraeditbuttons = false, $lessonpageid = null) {
        global $CFG;

        $activityname = format_string($lesson->name, true, $this->page->course->id);
        $title = $this->page->course->shortname.": ".$activityname;

        // Build the buttons
        $context = get_context_instance(CONTEXT_MODULE, $this->page->cm->id);
        if (has_capability('mod/lesson:edit', $context)) {
            $buttons = $this->output->update_module_button($this->page->cm->id, 'lesson');
            if ($extraeditbuttons) {
                if ($lessonpageid === null) {
                    print_error('invalidpageid', 'lesson');
                }
                if (!empty($lessonpageid) && $lessonpageid != LESSON_EOL) {
                    $options = array('id'=>$this->page->cm->id, 'redirect'=>'navigation', 'pageid'=>$lessonpageid);
                    $buttonform = html_form::make_button($CFG->wwwroot.'/mod/lesson/lesson.php', $options, get_string('editpagecontent', 'lesson'));
                    $buttons .= $this->output->button($buttonform);
                }
                $buttons = $this->output->box($buttons, 'edit_buttons');
            }
        } else {
            $buttons = '&nbsp;';
        }

    /// Header setup
        $this->page->requires->css('mod/lesson/lesson.css');
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        $this->page->set_button($buttons);
        $output = $this->output->header();

        if (has_capability('mod/lesson:manage', $context)) {

            $helpicon = new moodle_help_icon();
            $helpicon->text = $activityname;
            $helpicon->page = "overview";
            $helpicon->module = "lesson";

            $output .= $this->output->heading_with_help($helpicon);

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/lesson/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            $output .= $this->output->heading($activityname);
        }

        foreach ($lesson->messages as $message) {
            $output .= $this->output->notification($message[0], $message[1], $message[2]);
        }

        return $output;
    }

    /**
     * Returns the footer
     * @return string
     */
    public function footer() {
        return $this->output->footer();
    }

    /**
     * Returns HTML for a lesson inaccessible message
     *
     * @param string $message
     * @return <type>
     */
    public function lesson_inaccessible($message) {
        global $CFG;
        $output  =  $this->output->box_start('generalbox boxaligncenter');
        $output .=  $this->output->box_start('center');
        $output .=  $message;
        $output .=  $this->output->box('<a href="'.$CFG->wwwroot.'/course/view.php?id='. $this->page->course->id .'">'. get_string('returnto', 'lesson', format_string($this->page->course->fullname, true)) .'</a>', 'lessonbutton standardbutton');
        $output .=  $this->output->box_end();
        $output .=  $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to prompt the user to log in
     * @param lesson $lesson
     * @param bool $failedattempt
     * @return string
     */
    public function login_prompt(lesson $lesson, $failedattempt = false) {
        global $CFG;
        $output  = $this->output->box_start('password-form');
        $output .= $this->output->box_start('generalbox boxaligncenter');
        $output .=  '<form id="password" method="post" action="'.$CFG->wwwroot.'/mod/lesson/view.php" autocomplete="off">';
        $output .=  '<fieldset class="invisiblefieldset center">';
        $output .=  '<input type="hidden" name="id" value="'. $this->page->cm->id .'" />';
        if ($failedattempt) {
            $output .=  $this->output->notification(get_string('loginfail', 'lesson'));
        }
        $output .= get_string('passwordprotectedlesson', 'lesson', format_string($lesson->name)).'<br /><br />';
        $output .= get_string('enterpassword', 'lesson')." <input type=\"password\" name=\"userpassword\" /><br /><br />";
        $output .= '<div class="lessonbutton standardbutton"><a href="'.$CFG->wwwroot.'/course/view.php?id='. $this->page->course->id .'">'. get_string('cancel', 'lesson') .'</a></div> ';
        $output .= "<div class='lessonbutton standardbutton submitbutton'><input type='submit' value='".get_string('continue', 'lesson')."' /></div>";
        $output .=  '</fieldset></form>';
        $output .=  $this->output->box_end();
        $output .=  $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display dependancy errors
     *
     * @param object $dependentlesson
     * @param array $errors
     * @return string
     */
    public function dependancy_errors($dependentlesson, $errors) {
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= get_string('completethefollowingconditions', 'lesson', $dependentlesson->name);
        $output .= $this->output->box(implode('<br />'.get_string('and', 'lesson').'<br />', $errors),'center');
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a message
     * @param string $message
     * @param html_form $button
     * @return string
     */
    public function message($message, html_form $button = null) {
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= $message;
        if ($button !== null) {
            $output .= $this->output->box($this->output->button($button),'lessonbutton standardbutton');
        }
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a continue button
     * @param lesson $lesson
     * @param int $lastpageseen
     * @return string
     */
    public function continue_links(lesson $lesson, $lastpageseenid) {
        global $CFG;
        $output = $this->output->box(get_string('youhaveseen','lesson'), 'generalbox boxaligncenter');
        $output .= $this->output->box_start('center');

        $yeslink = html_link::make(new moodle_url($CFG->wwwroot.'/mod/lesson/view.php', array('id'=>$this->page->cm->id, 'pageid'=>$lastpageseenid, 'startlastseen'=>'yes')), get_string('yes'));
        $output .= $this->output->span($this->output->link($yeslink), 'lessonbutton standardbutton');

        $nolink = html_link::make(new moodle_url($CFG->wwwroot.'/mod/lesson/view.php', array('id'=>$this->page->cm->id, 'pageid'=>$lesson->firstpageid, 'startlastseen'=>'no')), get_string('no'));
        $output .= $this->output->span($this->output->link($nolink), 'lessonbutton standardbutton');

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a page to the user
     * @param lesson $lesson
     * @param lesson_page $page
     * @param object $attempt
     * @return string
     */
    public function display_page(lesson $lesson, lesson_page $page, $attempt) {
        // We need to buffer here as there is an mforms display call
        ob_start();
        echo $page->display($this, $attempt);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Returns HTML to display a collapsed edit form
     *
     * @param lesson $lesson
     * @param int $pageid
     * @return string
     */
    public function display_edit_collapsed(lesson $lesson, $pageid) {
        global $DB, $CFG;

        $manager = lesson_page_type_manager::get($lesson);
        $qtypes = $manager->get_page_type_strings();
        $npages = count($lesson->load_all_pages());

        $table = new html_table();
        $table->head = array(get_string('pagetitle', 'lesson'), get_string('qtype', 'lesson'), get_string('jumps', 'lesson'), get_string('actions', 'lesson'));
        $table->align = array('left', 'left', 'left', 'center');
        $table->wrap = array('', 'nowrap', '', 'nowrap');
        $table->tablealign = 'center';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        $table->data = array();

        $canedit = has_capability('mod/lesson:edit', get_context_instance(CONTEXT_MODULE, $this->page->cm->id));

        while ($pageid != 0) {
            $page = $lesson->load_page($pageid);
            $data = array();
            $data[] = "<a href=\"$CFG->wwwroot/mod/lesson/edit.php?id=".$this->page->cm->id."&amp;mode=single&amp;pageid=".$page->id."\">".format_string($page->title,true).'</a>';
            $data[] = $qtypes[$page->qtype];
            $data[] = implode("<br />\n", $page->jumps);
            if ($canedit) {
                $data[] = $this->page_action_links($page, $npages, true);
            } else {
                $data[] = '';
            }
            $table->data[] = $data;
            $pageid = $page->nextpageid;
        }

        return $this->output->table($table);
    }

    /**
     * Returns HTML to display the full edit page
     *
     * @param lesson $lesson
     * @param int $pageid
     * @param int $prevpageid
     * @param bool $single
     * @return string
     */
    public function display_edit_full(lesson $lesson, $pageid, $prevpageid, $single=false) {
        global $DB, $CFG;

        $manager = lesson_page_type_manager::get($lesson);
        $qtypes = $manager->get_page_type_strings();
        $npages = count($lesson->load_all_pages());
        $canedit = has_capability('mod/lesson:edit', get_context_instance(CONTEXT_MODULE, $this->page->cm->id));

        $content = '';
        if ($canedit) {
            $content = $this->add_page_links($lesson, $prevpageid);
        }

        $options = new stdClass;
        $options->noclean = true;

        while ($pageid != 0 && $single!=='stop') {
            $page = $lesson->load_page($pageid);

            $pagetable = new html_table();
            $pagetable->align = array('right','left');
            $pagetable->width = '100%';
            $pagetable->tablealign = 'center';
            $pagetable->cellspacing = 0;
            $pagetable->cellpadding = '5px';
            $pagetable->data = array();

            $pageheading = new html_table_cell();

            $pageheading->text = format_string($page->title);
            if ($canedit) {
                $pageheading->text .= ' '.$this->page_action_links($page, $npages);
            }
            $pageheading->style = 'text-align:center';
            $pageheading->colspan = 2;
            $pageheading->scope = 'col';
            $pagetable->head = array($pageheading);

            $cell = new html_table_cell();
            $cell->colspan = 2;
            $cell->style = 'text-align:center';
            $cell->text = format_text($page->contents, FORMAT_MOODLE, $options);
            $pagetable->data[] = html_table_row::make(array($cell));

            $cell = new html_table_cell();
            $cell->colspan = 2;
            $cell->style = 'text-align:center';
            $cell->text = '<strong>'.$qtypes[$page->qtype] . $page->option_description_string().'</strong>';
            $pagetable->data[] = html_table_row::make(array($cell));

            $pagetable = $page->display_answers($pagetable);

            $content .= $this->output->table($pagetable);

            if ($canedit) {
                $content .= $this->add_page_links($lesson, $pageid);
            }

            // check the prev links - fix (silently) if necessary - there was a bug in
            // versions 1 and 2 when add new pages. Not serious then as the backwards
            // links were not used in those versions
            if ($page->prevpageid != $prevpageid) {
                // fix it
                $DB->set_field("lesson_pages", "prevpageid", $prevpageid, array("id" => $page->id));
                debugging("<p>***prevpageid of page $page->id set to $prevpageid***");
            }

            $prevpageid = $page->id;
            $pageid = $page->nextpageid;

            if ($single === true) {
                $single = 'stop';
            }

        }

        return $this->output->box($content, 'edit_pages_box');
    }

    /**
     * Returns HTML to display the add page links
     *
     * @param lesson $lesson
     * @param int $prevpageid
     * @return string
     */
    public function add_page_links(lesson $lesson, $prevpageid=false) {
        global $CFG;

        $links = array();

        $importquestionsurl = new moodle_url($CFG->wwwroot.'/mod/lesson/import.php',array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_link::make($importquestionsurl, get_string('importquestions', 'lesson'));

        $manager = lesson_page_type_manager::get($lesson);
        $links = array_merge($links, $manager->get_add_page_type_links($prevpageid));

        $addquestionurl = new moodle_url($CFG->wwwroot.'/mod/lesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_link::make($addquestionurl, get_string('addaquestionpagehere', 'lesson'));
        
        foreach ($links as $key=>$link) {
            $links[$key] = $this->output->link($link);
        }

        return $this->output->box(implode(" | \n", $links), 'addlinks');
    }

    /**
     * Return HTML to display add first page links
     * @param lesson $lesson
     * @return string
     */
    public function add_first_page_links(lesson $lesson) {
        global $CFG;
        $prevpageid = 0;

        $output = $this->output->heading(get_string("whatdofirst", "lesson"), 3);
        $links = array();

        $importquestionsurl = new moodle_url($CFG->wwwroot.'/mod/lesson/import.php',array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_link::make($importquestionsurl, get_string('importquestions', 'lesson'));

        $importppturl = new moodle_url($CFG->wwwroot.'/mod/lesson/importppt.php',array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_link::make($importppturl, get_string('importppt', 'lesson'));

        $manager = lesson_page_type_manager::get($lesson);
        $newpagelinks = $manager->get_add_page_type_links($prevpageid);
        foreach ($newpagelinks as $link) {
            $link->url->param('firstpage', 1);
            $links[] = $link;
        }

        $addquestionurl = new moodle_url($CFG->wwwroot.'/mod/lesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid, 'firstpage'=>1));
        $links[] = html_link::make($addquestionurl, get_string('addaquestionpage', 'lesson'));

        foreach ($links as $key=>$link) {
            $links[$key] = $this->output->link($link);
        }

        return $this->output->box($output.'<p>'.implode('</p><p>', $links).'</p>', 'generalbox firstpageoptions');
    }

    /**
     * Returns HTML to display action links for a page
     *
     * @param lesson_page $page
     * @param bool $printmove
     * @param bool $printaddpage
     * @return string
     */
    public function page_action_links(lesson_page $page, $printmove, $printaddpage=false) {
        global $CFG;

        $actions = array();

        if ($printmove) {
            $printmovehtml = new moodle_url($CFG->wwwroot.'/mod/lesson/lesson.php', array('id'=>$this->page->cm->id, 'action'=>'move', 'pageid'=>$page->id, 'sesskey'=>sesskey()));
            $actions[] = html_link::make($printmovehtml, '<img src="'.$this->output->old_icon_url('t/move').'" class="iconsmall" alt="'.get_string('move').'" />');
        }
        $url = new moodle_url($CFG->wwwroot.'/mod/lesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$page->id, 'edit'=>1));
        $actions[] = html_link::make($url, '<img src="'.$this->output->old_icon_url('t/edit').'" class="iconsmall" alt="'.get_string('update').'" />');

        $url = new moodle_url($CFG->wwwroot.'/mod/lesson/view.php', array('id'=>$this->page->cm->id, 'pageid'=>$page->id));
        $actions[] = html_link::make($url, '<img src="'.$this->output->old_icon_url('t/preview').'" class="iconsmall" alt="'.get_string('preview').'" />');

        $url = new moodle_url($CFG->wwwroot.'/mod/lesson/lesson.php', array('id'=>$this->page->cm->id, 'action'=>'confirmdelete', 'pageid'=>$page->id, 'sesskey'=>sesskey()));
        $actions[] = html_link::make($url, '<img src="'.$this->output->old_icon_url('t/delete').'" class="iconsmall" alt="'.get_string('delete').'" />');

        if ($printaddpage) {
            $options = array();
            $manager = lesson_page_type_manager::get($page->lesson);
            $links = $manager->get_add_page_type_links($page->id);
            foreach ($links as $link) {
                $options[$link->url->param('qtype')] = $link->text;
            }
            $options[0] = get_string('question', 'lesson');
            
            $addpageurl = new moodle_url($CFG->wwwroot.'/mod/lesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$page->id, 'sesskey'=>sesskey()));
            $addpageselect = html_select::make_popup_form($addpageurl, 'qtype', $options, 'addpageafter'.$page->id);
            $addpageselect->nothinglabel = get_string('addanewpage', 'lesson').'...';
            $addpageselector = $this->output->select($addpageselect);
        }

        foreach ($actions as $key=>$action) {
            $actions[$key] = $this->output->link($action);
        }
        if (isset($addpageselector)) {
            $actions[] = $addpageselector;
        }

        return implode(' ', $actions);
    }

    /**
     * Prints the on going message to the user.
     *
     * With custom grading On, displays points
     * earned out of total points possible thus far.
     * With custom grading Off, displays number of correct
     * answers out of total attempted.
     *
     * @param object $lesson The lesson that the user is taking.
     * @return void
     **/

     /**
      * Prints the on going message to the user.
      *
      * With custom grading On, displays points
      * earned out of total points possible thus far.
      * With custom grading Off, displays number of correct
      * answers out of total attempted.
      *
      * @param lesson $lesson
      * @return string
      */
    public function ongoing_score(lesson $lesson) {
        global $USER, $DB;

        $context = get_context_instance(CONTEXT_MODULE, $this->page->cm->id);
        if (has_capability('mod/lesson:manage', $context)) {
            return '<p align="center">'.get_string('teacherongoingwarning', 'lesson').'</p>';
        } else {
            $ntries = $DB->count_records("lesson_grades", array("lessonid"=>$lesson->id, "userid"=>$USER->id));
            if (isset($USER->modattempts[$lesson->id])) {
                $ntries--;
            }
            $gradeinfo = lesson_grade($lesson, $ntries);
            $a = new stdClass;
            if ($lesson->custom) {
                $a->score = $gradeinfo->earned;
                $a->currenthigh = $gradeinfo->total;
                return $this->output->box(get_string("ongoingcustom", "lesson", $a), "generalbox boxaligncenter");
            } else {
                $a->correct = $gradeinfo->earned;
                $a->viewed = $gradeinfo->attempts;
                return $this->output->box(get_string("ongoingnormal", "lesson", $a), "generalbox boxaligncenter");
            }
        }
    }

    /**
     * Returns HTML to display a progress bar of progression through a lesson
     *
     * @param lesson $lesson
     * @return string
     */
    public function progress_bar(lesson $lesson) {
        global $CFG, $USER, $DB;

        $context = get_context_instance(CONTEXT_MODULE, $this->page->cm->id);

        // lesson setting to turn progress bar on or off
        if (!$lesson->progressbar) {
            return '';
        }

        // catch teachers
        if (has_capability('mod/lesson:manage', $context)) {
            return $this->output->notification(get_string('progressbarteacherwarning2', 'lesson'));
        }
        
        if (!isset($USER->modattempts[$lesson->id])) {
            // all of the lesson pages
            $pages = $lesson->load_all_pages();
            foreach ($pages as $page) {
                if ($page->prevpageid == 0) {
                    $pageid = $page->id;  // find the first page id
                    break;
                }
            }

            // current attempt number
            if (!$ntries = $DB->count_records("lesson_grades", array("lessonid"=>$lesson->id, "userid"=>$USER->id))) {
                $ntries = 0;  // may not be necessary
            }

            
            $viewedpageids = array();
            if ($attempts = $lesson->get_attempts($ntries, true)) {
                $viewedpageids = array_merge($viewedpageids, array_keys($attempts));
            }

            // collect all of the branch tables viewed
            if ($viewedbranches = $DB->get_records_select("lesson_branch", array ("lessonid"=>$lesson->id, "userid"=>$USER->id, "retry"=>$ntries), 'timeseen DESC', 'pageid, id')) {
                $viewedpageids = array_merge($viewedpageids, array_keys($viewedbranches));
            }

            // Filter out the following pages:
            //      End of Cluster
            //      End of Branch
            //      Pages found inside of Clusters
            // Do not filter out Cluster Page(s) because we count a cluster as one.
            // By keeping the cluster page, we get our 1
            $validpages = array();
            while ($pageid != 0) {
                $pageid = $pages[$pageid]->valid_page_and_view($validpages, $viewedpageids);
            }

            // progress calculation as a percent
            $progress = round(count($viewedpageids)/count($validpages), 2) * 100;
        } else {
            $progress = 100;
        }

        // print out the Progress Bar.  Attempted to put as much as possible in the style sheets.
        $cells = array();
        if ($progress != 0) {  // some browsers do not repsect the 0 width.
            $cells[0] = new html_table_cell();
            $cells[0]->style = 'width:'.$progress.'%';
            $cells[0]->set_classes('progress_bar_completed');
            $cells[0]->text = ' ';
        }
        $cells[] = '<div class="progress_bar_token"></div>';

        $table = new html_table();
        $table->set_classes(array('progress_bar_table', 'center'));
        $table->data = array(html_table_row::make($cells));
        
        return $this->output->box($this->output->table($table), 'progress_bar');
    }

    /**
     * Returns HTML to show the start of a slideshow
     * @param lesson $lesson
     */
    public function slideshow_start(lesson $lesson) {
        $attributes = array();
        $attributes['class'] = 'slideshow';
        $attributes['style'] = 'background-color:'.$lesson->bgcolor.';height:'.$lesson->height.'px;width:'.$lesson->width.'px;';
        $output = $this->output_start_tag('div', $attributes);
    }
    /**
     * Returns HTML to show the end of a slideshow
     */
    public function slideshow_end() {
        $output = $this->output_end_tag('div');
    }
    /**
     * Returns a P tag containing contents
     * @param string $contents
     * @param string $class
     */
    public function paragraph($contents, $class='') {
        $attributes = array();
        if ($class !== '') {
            $attributes['class'] = $class;
        }
        $output = $this->output_tag('p', $attributes, $contents);
    }
    /**
     * Returns HTML to display add_highscores_form
     * @param lesson $lesson
     * @return string
     */
    public function add_highscores_form(lesson $lesson) {
        global $CFG;
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= $this->output->box_start('mdl-align');
        $output .= '<form id="nickname" method ="post" action="'.$CFG->wwwroot.'/mod/lesson/highscores.php" autocomplete="off">
             <input type="hidden" name="id" value="'.$this->page->cm->id.'" />
             <input type="hidden" name="mode" value="save" />
             <input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $output .= get_string("entername", "lesson").": <input type=\"text\" name=\"name\" size=\"7\" maxlength=\"5\" />";
        $output .= $this->output->box("<input type='submit' value='".get_string('submitname', 'lesson')."' />", 'lessonbutton center');
        $output .= "</form>";
        $output .= $this->output->box_end();
        $output .= $this->output->box_end();
        return $output;
    }
}