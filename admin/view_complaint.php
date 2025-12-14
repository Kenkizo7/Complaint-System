<?php
include 'db.php';
$id = $_GET['id'];

$query = mysqli_query($conn, "SELECT * FROM complaints WHERE id='$id'");
$data = mysqli_fetch_assoc($query);
?>

<h2>Complaint Details</h2>

<p><strong>Student ID:</strong> <?php echo $data['student_id']; ?></p>
<p><strong>Description:</strong> <?php echo $data['description']; ?></p>
<p><strong>Against:</strong> <?php echo $data['complaint_against_type']; ?></p>
<p><strong>Status:</strong> <?php echo $data['status']; ?></p>
<p><strong>Co-Complainant:</strong> <?php echo $data['co_complainant_name']; ?></p>

<form method="post">
    <select name="status">
        <option>Pending</option>
        <option>In Progress</option>
        <option>Resolved</option>
    </select>
    <button name="update">Update Status</button>
</form>

<?php
if (isset($_POST['update'])) {
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE complaints SET status='$status' WHERE id='$id'");
    echo "<script>alert('Status Updated');window.location='manage_complaints.php';</script>";
}
?>
