<?php
include 'db.php';

if (isset($_POST['create'])) {
    $id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    mysqli_query($conn, "INSERT INTO students VALUES('$id','$name','$email','$password')");
    echo "<script>alert('Student Account Created');</script>";
}
?>

<h2>Create Student Account</h2>

<form method="post">
    <input type="text" name="student_id" placeholder="Student ID" required><br>
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button name="create">Create</button>
</form>
