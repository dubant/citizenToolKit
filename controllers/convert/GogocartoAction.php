<?php
class GogocartoAction extends CAction {

    public function run($url=null, $text_filter=null) {

		$res = Convert::convertGogoCarto($url, $text_filter);

  		if (isset($res)) {
			Rest::json($res);
		}

		Yii::app()->end();
	}
}

?>