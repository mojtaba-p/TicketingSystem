<?php
global $ts_form_errors;


if ( $ts_form_errors->has_errors() ) {
	

	foreach ($ts_form_errors->errors as $ts_err) {
		$ts_errs .= "{$ts_err[0]}<br>";
	}

	?>

	<div class="alert alert-danger text-right">
		<?=$ts_errs; ?>
	</div>
	<?php
}