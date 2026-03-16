<div class="wrap">
<?php

$report_request_url = admin_url('admin-post.php')
?>
<h1>Reports</h1>

<h2> Generate New Report </h2>

<form action="<?php echo esc_url( $report_request_url); ?>" method="POST">

  <input type="hidden" name="action" value="download_admin_report">

  <?php wp_nonce_field( 'download_admin_report_nonce', '_wpnonce' ); ?>
  
  <table class="form-table">
    <tr>
      <th><label>Choose Exercises for Report</label></th>
    </tr>          

    <tr>
      <td><select class="select1" multiple name="exercises[]">      
      <?php foreach ($exercises as $exercise): ?>
      <option value="<?php echo esc_attr($exercise['exercise_id']); ?>"<?php if ($exercise['exercise_type'] === "TEXT") { echo 'selected'; }?>><?php echo esc_html($exercise['exercise_name']); ?></option>
      <?php endforeach; ?>
      </select></td>
    </tr>          
</table>     
        <?php submit_button('Generate Report', 'primary', 'generate_report'); ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/dual-listbox/dist/dual-listbox.min.js"></script>
<link
    href="https://cdn.jsdelivr.net/npm/dual-listbox/dist/dual-listbox.css"
    rel="stylesheet"
/>
