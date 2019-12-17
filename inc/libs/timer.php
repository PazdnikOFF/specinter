<?php

class Timer {
	private static $times = array();
	private static $marks = array();

	public static function startTimer() {
		self::$times[] = microtime(true);
	}	

	public static function setTimerMark($name) {
		$now = microtime(true);
		$count = count(self::$times);
		
		$num = $count - 1;
		self::$marks[$name] = $now - self::$times[$num];
		

		self::$times[] = $now;	
	}

	public static function debugTimerMarks() {		
		$i = 0;
		foreach (self::$marks as $key => $val) {
			echo "<p>$key - ".$val."</p>";
			$i ++;
		}		
	}

	public function resetTimer() {
		self::$times = array();
		self::$marks = array();
	}
}
?>
