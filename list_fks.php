<?php
require_once "classes/database.php";

$sql = "
SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME 
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = 'conselho' 
    AND REFERENCED_TABLE_NAME IS NOT NULL;
";

$res = DBExecute($sql);
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
