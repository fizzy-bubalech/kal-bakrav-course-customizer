<div class="wrap">
    <h1>Manage Results</h1>

    <h2>Add New Result</h2>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="user_id">User</label></th>
                <td>
                    <select name="user_id" id="user_id" required>
                    <option value="">Select a user</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo esc_attr(
                                $user["ID"]
                            ); ?>"><?php echo esc_html(
    $user["display_name"]
); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="exercise_id">Exercise</label></th>
                <td>
                    <select name="exercise_id" id="exercise_id" required>
                        <?php foreach ($exercises as $exercise): ?>
                            <option value="<?php echo esc_attr(
                                $exercise["exercise_id"]
                            ); ?>"><?php echo esc_html(
    $exercise["exercise_name"]
); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="result">Result</label></th>
                <td><input name="result" id="result" type="number" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="result_date">Date</label></th>
                <td><input name="result_date" id="result_date" type="datetime-local" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="is_metric">Is Metric?</label></th>
                <td><input name="is_metric" id="is_metric" type="checkbox"></td>
            </tr>
        </table>
        <?php submit_button("Add Result", "primary", "add_result"); ?>
    </form>

    <h2>Existing Results</h2>
       <div class="tablenav top">
           <div class="alignleft actions">
               <select id="filter-user">
                   <option value="">All Users</option>
                   <?php foreach ($users as $user): ?>
                       <option value="<?php echo esc_attr(
                           $user["ID"]
                       ); ?>"><?php echo esc_html(
    $user["display_name"]
); ?></option>
                   <?php endforeach; ?>
               </select>
               <select id="filter-exercise">
                   <option value="">All Exercises</option>
                   <?php foreach ($exercises as $exercise): ?>
                       <option value="<?php echo esc_attr(
                           $exercise["exercise_id"]
                       ); ?>"><?php echo esc_html(
    $exercise["exercise_name"]
); ?></option>
                   <?php endforeach; ?>
               </select>
               <input type="text" id="search-input" placeholder="Search...">
               <button id="apply-filters" class="button">Apply Filters</button>
           </div>
       </div>
       <table class="wp-list-table widefat fixed striped" id="results-table">
           <thead>
               <tr>
                   <th class="sortable" data-sort="result_id">ID <span class="sort-indicator"></span></th>
                   <th class="sortable" data-sort="user_name">User <span class="sort-indicator"></span></th>
                   <th class="sortable" data-sort="exercise_name">Exercise <span class="sort-indicator"></span></th>
                   <th class="sortable" data-sort="result">Result <span class="sort-indicator"></span></th>
                   <th class="sortable" data-sort="result_date">Date <span class="sort-indicator"></span></th>
                   <th class="sortable" data-sort="is_metric">Is Metric <span class="sort-indicator"></span></th>
                   <th>Action</th>
               </tr>
           </thead>
           <tbody>
               <!-- Results will be dynamically populated here -->
           </tbody>
       </table>
   </div>

   <style>
       .sortable {
           cursor: pointer;
       }
       .sort-asc .sort-indicator:after {
           content: "\25B2";
       }
       .sort-desc .sort-indicator:after {
           content: "\25BC";
       }
   </style>
