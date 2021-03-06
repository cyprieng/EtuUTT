<?php

namespace Etu\Core\UserBundle\Schedule\Model;

use Etu\Core\UserBundle\Entity\Course;

/**
 * Course
 */
class CourseHalf
{
	/**
	 * @var array
	 */
	protected $courses;

	/**
	 * @param Course $course
	 */
	public function addCourse(Course $course)
	{
		if ($course->getWeek() == 'A') {
			$this->courses['A'] = $course;
		} elseif ($course->getWeek() == 'B') {
			$this->courses['B'] = $course;
		}
	}

	/**
	 * @return bool
	 */
	public function hasCourseBothWeeks()
	{
		return $this->hasCourseWeekA() && $this->hasCourseWeekB();
	}

	/**
	 * @return bool
	 */
	public function hasCourseWeekA()
	{
		return isset($this->courses['A']);
	}

	/**
	 * @return Course
	 */
	public function getCourseWeekA()
	{
		if (! $this->hasCourseWeekA()) {
			return false;
		}

		return $this->courses['A'];
	}

	/**
	 * @return bool
	 */
	public function hasCourseWeekB()
	{
		return isset($this->courses['B']);
	}

	/**
	 * @return Course
	 */
	public function getCourseWeekB()
	{
		if (! $this->hasCourseWeekB()) {
			return false;
		}

		return $this->courses['B'];
	}
}
