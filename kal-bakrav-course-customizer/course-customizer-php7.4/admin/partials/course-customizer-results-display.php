<div class="wrap">
    <h1>Manage Results</h1>

    <h2>Add New Result</h2>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="user_id">User</label></th>
                <td>
                    <select name="user_id" id="user_id" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo esc_attr($user['ID']); ?>"><?php echo esc_html($user['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="exercise_id">Exercise</label></th>
                <td>
                    <select name="exercise_id" id="exercise_id" required>
                        <?php foreach ($exercises as $exercise): ?>
                            <option value="<?php echo esc_attr($exercise['exercise_id']); ?>"><?php echo esc_html($exercise['exercise_name']); ?></option>
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
        <?php submit_button('Add Result', 'primary', 'add_result'); ?>
    </form>

    <h2>Existing Results</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Exercise</th>
                <th>Result</th>
                <th>Date</th>
                <th>Is Metric</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><?php echo esc_html($result['result_id']); ?></td>
                <td><?php echo esc_html($result['user_name']); ?></td>
                <td><?php echo esc_html($result['exercise_name']); ?></td>
                <td><?php echo esc_html($result['result']); ?></td>
                <td><?php echo esc_html($result['result_date']); ?></td>
                <td><?php echo $result['is_metric'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this result?');">
                        <input type="hidden" name="result_id" value="<?php echo esc_attr($result['result_id']); ?>">
                        <?php submit_button('Delete', 'delete', 'delete_result', false); ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>