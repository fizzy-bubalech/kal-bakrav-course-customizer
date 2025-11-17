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
                <th scope="row"><label for="exercise_type">Exercise Type</label></th>
                <td>
                    <select name="exercise_type" id="exercise_type">
                        <option value="COUNT">Reps-based</option>
                        <option value="TIME">Time-based</option>
                        <option value="TEXT">Text-based</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="min">Min Value (in seconds if time)</label></th>
                <td><input name="min" id="min" type="number" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="max">Max Value (in seconds if time)</label></th>
                <td><input name="max" id="max" type="number" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="description">Exercise Description</label></th>
                <td><textarea name="description" id="description" type="text" cols=50 rows=3 required></textarea></td>
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
                <th>Min</th>
                <th>Max</th>
                <th>Exercise Type</th>
                <th>Exercise Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exercises as $exercise): ?>
                <tr>
                    <td><?php echo esc_html($exercise['exercise_id']); ?></td>
                    <td><?php echo esc_html($exercise['exercise_name']); ?></td>
                    <td><?php echo $exercise['is_time'] ? 'Yes' : 'No'; ?></td>
                    <td class="trigger inline-cell"><?php echo $exercise['min'] ?><span class="hover-target dashicons dashicons-edit"></span></td>
                    <td><?php echo $exercise['max'] ?></td>
                    <td><?php echo $exercise['exercise_type'] ?></td>
                    <td><?php echo $exercise['exercise_description'] ?></td>
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
