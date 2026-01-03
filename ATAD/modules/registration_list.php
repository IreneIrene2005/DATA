 <?php
include "includes/connection.php";

$sql = "SELECT * FROM tbl_registration";
$result = $conn->query($sql);
$conn->close();

?>
 
 
 <table class="table datatable">
                <thead>
                  <tr>
                    <th>
                      Registration ID
                    </th>
                    <th>FullName</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th colspan="3">Actions</th>
                  </tr>
                </thead>
                <tbody>
                     <?php while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo $row["registration_id"]; ?></td>
                     <td><?php echo $row["full_name"]; ?></td>
                     <td><?php echo $row["email"]; ?></td>
                     <td><?php echo $row["password"]; ?></td>
                     <td>
                        <buton type="button" class="btn btn-primary">Delete</buton>
                     </td>
                  </tr>
                  <?php endwhile; ?>   
                </tbody>
              </table>