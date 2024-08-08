<div class="wrap">
    <h1>Manage Exercises</h1>

    <h2>Add New Exercise</h2>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="exercise_name">Exercise Name</label></th>
                <td><input name="exercise_name" id="exercise_name" type="text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="is_time">Is Time-based?</label></th>
                <td><input name="is_time" id="is_time" type="checkbox"></td>
            </tr>
        </table>
        <?php submit_button('Add Exercise', 'primary', 'add_exercise'); ?>
    </form>

    <h2>Existing Exercises</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Is Time-based</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exercises as $exercise): ?>
            <tr>
                <td><?php echo esc_html($exercise['exercise_id']); ?></td>
                <td><?php echo esc_html($exercise['exercise_name']); ?></td>
                <td><?php echo $exercise['is_time'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this exercise?');">
                        <input type="hidden" name="exercise_id" value="<?php echo esc_attr($exercise['exercise_id']); ?>">
                        <?php submit_button('Delete', 'delete', 'delete_exercise', false); ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>