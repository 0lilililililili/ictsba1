# HKDSE ICT SBA

## Extracurricular Activity Management System (ECAMS) Prototype

## CHEN KA YING 11M 5

### Submission Date: 8.28.2026
 
# ABSTRACT
	ABC College is planning to develop an Extracurricular Activity Management System (ECAMS) to streamline the process of tracking student participation and attendance in extracurricular activities. The new system allows different groups of users to marking, check, and analyze attendance and participation statuses for their assigned activities using a tablet or mobile device.
	
	Here, the development process of a prototype for this system will be documented and elucidated.
	
	This report will include relationships, ER diagram, views of the database; and the creating process, reference codes, as well as usage demonstration of both the database and the dynamic webpage user interface.
 
# Part A: Understanding of the ECAMS System
## 1.	Relationships
  According to the background information provided, there are mainly 3 groups of stakeholders using this system: Students (both regular members and activity monitors), Teachers(both club teachers and administrators), and Clubs.
  
  Relationships of the stakeholders are as follows:
  
    1.	Student must join at least 1 club,
	
    A club must have at least have more than 1 student;
	
    2.	A club must have only 1 teacher in charge,
	
    A teacher may manage only 1 club;
	
    3.	A club must have at least one activity,
	
    An activity must only be organized by one club;
	
    4.	Student may join more than 1 activity,
	
    An activity must consist of more than 1 student;
	
  However, note that student-club and student-activity result in many-to-many relationships, we need to resolve them into a one-to-many relationship using associative entities 
  to prevent data redundancy. To resolve the student-club table and the student-activity table, they each need one associative entity respectively. Thus, both relationships are now modified as below:
  
    1.	Student must apply at least one enrollment,
	
    One enrollment must be committed by only one student;
	
    2.	A club must consists of many enrollments,
	
    One enrollment must only be applicable to one club;
	
    3.	Students can request many participations,
	
    One participation record must only be requested by one student;
	
    4.	One participation record must only be referring to one activity,
	
    One activity must consists of more than one participant request;
	
## 2.	Entity
  Now that we have all the relationships, there should be total of 6 entities:
  
  student, enrollment, club, teacher, participation, activity.
  

## 3.	Attribute and Schema
  For the table student, it should consists the basic information of the students, including their id, name, class and class number: 
  
  student {sid, sname, scls, scno};
  
  For the table teacher, it includes the details of including teachers’ id and name: 
  
  teacher {tid, tname};
  
  For the table club, it should contain the details of each club, namely their id, name, and responsible teacher: 
  
  club {cid, cname, tid};
  
  For the table enrollment, it should consist the details of each student’s application to each club: 
  
  enrollment {sid, cid};
  
  For the table participation, there should be information on the student, activity involved, and its status(1=attended, 2=absent, 3=late): 
  
  participation {sid, aid, status};
  
  For the table activity, it should contain details like activity id, name, date, venue, attended rate, its student monitor, and the club that holds it: 
  
  activity {aid, aname, adate, venue, attendance, cid, stuMon};
  
# Part B: Database
## 1.	Normalization
  Taking note of the ECAMS system’s nature of needing frequent updates, it would be prone to insertion, update, and deletion anomaly. So it would be best for our database to follow a 3NF format (3rd normal form: no transitive functional dependency, no partial functional dependency, no repeating attributes) to prevent anomalies above.
  
  First, we already confirmed the tables above have no repeating attributes when creating them. Then, after checking all tables, we confirmed that there is no transitive functional dependency, as all other attributes only depend on the primary key of the table. Lastly, we should also check if all tables have no partial functional dependency.
  
  For other tables apart from participation, there should be no dispute over the fact. However, as a table with composite PK, we should check participation carefully.
  
  Upon examination, we can see that participation only has 3 attributes, with SID+AID being the primary key. Hence, we only needed to check if the remaining attribute, STATUS, if fully dependent on SID+AID and not just one of them. This is true since the status of a distinct participation request requires information on both the student who requested it and their activity.
  
  Thus, all requirements of the 3NF is fulfilled and no changes are need to be made to the initial schema.
 
## 2.	ER Diagram
  With reference to the schema in the previous section, the ER diagram is as follows:  
<img width="927" height="618" alt="ERdiagram" src="https://github.com/user-attachments/assets/997c5af4-2f9e-4d7e-8aed-eafbc0828b42" />


## 3.	Points to Note: Attendance-Status Field Relationship
  Before testing whether this system works, allow me to explain the relationship of certain attributes and tables. There should be no problems when inserting values into the tables student, teacher, club, and enrollment, as these data are only one-way independent (where data in table A depends on B, and data in table B is not, both directly or indirectly, dependent on A). Just that we have to insert data in the order of ‘student -> teacher -> club -> enrollment’ as club and enrollment have foreign keys that reference on student and teacher. 
  
  However, when we closely inspect the tables participation and activity, we can see there are some logic loopholes, as some data are two-way dependent. Note that attendance means the attended rate of some activity, whose data value depends on the status attribute of the table participation. On the other hand, status is dependent on aid+sid, which references the student table and most importantly the activity table. Long story short, there would be an error when inserting values into the two tables no matter the order of doing so, as they are dependent on each other. We can simply not include the field when inserting data, but that does not solve our problems.
  
  Against this backdrop, we first set the field attendance is null by default. Then, we have to create automatic mechanism that sets off and alter attendance every time status is updated. Thus, our solution is to use the ‘trigger’ key to update the field in the background.
  
  Of course, another way would have been not creating this field in the first place. But by doing so, if we have to analyze the activity performance by taking its attendance rate and so on, we have to type and execute a very long command every time. So for the sake of user experience and convenience, it is better include this field and have it auto update in the background instead.

## 4.	SQL Query Demonstration
  For convenience, we would assume that all sid starts with the letter ‘s’, all tid starts with the letter ‘t’, all cid starts with the letter ‘c’, and all aid starts with letter ‘a’.
  
  First, we should create the database ecams using the SQL code below to store tables and data.
  
  For the field attendance, the code below will set off a trigger whenever a participation record is inserted, updated or deleted.
  
  Upon creating all tables, we then need to insert data into them.
  
  Since we set all the attributes to ‘delete/update on cascade’ if referencing the table student, deleting the record in student will remove all records across tables. This can be done using the ‘delete from’ command below.
  
  As demonstrated, we have finally developed a usable database successfully.
 
## 5.	Authorization
  As the background information indicates, there should be 4 types of users in this system: regular students, students who are activity monitors, club teachers, and teacher in charge as administrators.
  
  That said, they should be distinguished and have access to different degrees of necessary information to protect user’s privacy. In this case, a view is created for each of these roles.
  
  1. Admins supervising the database have editing access to every data (without view);
  
  2. Club teachers can only edit their own data, students’ participation status, and data of their clubs and activities.
  
  3. Student monitors can edit their own data, participation status of students in their assigned activity, and data of the activities.
  
  4. Regular students can only edit their own data.
  
# Part C: User Interface Dynamic Webpage
## 1.	Design Logic
	The aim of this webpage will be to allow different types of users to get access to the information they need. To distinguish between the users, we first needed a login page to verify their identity. 
	
Then, according to their roles, their information page shows different outputs. In the information page, it should include all the sql views and present them as dashboards.
Underneath each view, there should be an ‘edit’ and ‘insert’ button for users to modify, add or delete data.

## 2.	Webpage Components
  For all webpages, even though we did not include the enlarge font size button directly, users can still zoom in / out on their own device to ensure readability.

### 1.	Database
  Before creating the login page where users enter their role (student / monitor / teacher / admin), id and password, we have to add missing key components to our database: the role and password attributes. For convenience, assume the teacher with tid = ‘t0001’ is the admin and all passwords are initially ‘12345678’:

### 2.	Login Page
  For the login page, we first design the outlook of the page. As shown in the picture and code below, the user first chooses their role (which will be verified by checking table student or teacher) with a radio button, which increased user friendliness and reduce input error; it decides what data they can be exposed to, then, they enter their id (sid / tid) and password to log in.
  
  After clicking the ‘log in’ button, we will then process the request in the file process.php, which checks if the role, user id and password matches the record in student or teacher. The system will show ‘wrong role’ or ‘invalid password’ if theres an error in the users’ input.
       
### 3.	Dashboard View
  After verifying the user, the webpage will be redirected to the dashboard. There, the data will be separated into many sections: personal data (table student or teacher), request records (enrollment and participation), club (if user is a teacher), activity (if user is a student monitor or a teacher). In the teachers’ and student monitors’ view, participation will be further separated into sections grouping by their aid.
  
  At first, all data in dashboards are read-only before pressing the ‘modify’ button. This prevents any action that alters our data unintentionally.

### 4.	Update and Delete
  When we press ‘modify’, the status ‘read-only’ will turn to ‘disabled’ status. This allows users to edit their authorized data.
  
  Upon pressing ‘modify’, users can freely type whatever they need to change in the text box. After they press confirm, the record will be changed and also the data in the ecams database, then the status will switch back to read-only; if the user pressed ‘discard’, the changes made in the text box will switch back to the initial value before the change and status will switch back to read-only.

### 5.	Insert
  As the admin, the user has the authority to insert to rewrite every record in the database. By pressing the ‘overwrite/insert’ button, they can modify by overwriting existing records or insert new records using the same page. To increase user friendliness and reduce transcription or transposition error, a date-picker.is used for picking activity dates etc.
  
  After user action or wanting to discard changes, simply press the ‘go back to data’ button to redirect back to the dashboards.
      
## 6.	Rollback
  If a user confirmed a wrong modification, an undo button will appear on the top right corner. This allows users to retrieve the changes made to the data. However, the undo button can only perform rollback once after each change due to its mechanism.
  
	For how this undo button works, it slightly differs from the default rollback function in MySQL. 
	
  Due to our added triggers on participation and activity, the initial rollback function provided may not work and will potentially cause errors to our database. 
  
  Additionally, the original ‘rollback’ command requires the table in question’s connection session to be wide open. However, the nature of our webpage code violates this requirement as it only runs of a request-response cycle. In other words, once users click ‘confirm’ or ‘discard’, the response script code finished running. The moment the script ends, PHP automatically terminates the MySQL connection on the command ‘$conn->close()’. Any uncommitted transactions are permanently abandoned or cleared by the database.
  
  Even without the ‘$conn->close()’ command, original rollback function still would not work because the php codes are still a request-response script, which would terminate all connections and resources after running. When it cuts off the connection with the SQL database, it dismisses all uncommitted transactions automatically to protect database integrity.
  
  Thus, our approach on this function will be taking a copy of the initial data when users click ‘modify’ or ‘overwrite / insert’ (for admins), then store it as the output of the undo function. This ‘undo’ function will act as a new request using a new connection to overwrite the wrong changes on the previous data.
  
  But since this method only works every time the user clicks ‘modify’, it means that we can only restore the section’s data once ------ right before user made changes (the data of a whole session will be restored as one ‘modify’ button is corresponded to one session). Thus this is our only limitation.

### 7.	Log out
  After viewing or editing the dashboard, users will log out to ensure data security. They can do this by pressing the red ‘log out’ button on the top right corner. This will direct them straight back to the login page.
  
  Thus, we have finished developing and documenting the prototype of the ECAMS system. This is the end of the documentation report.

## Below are references and disclaimers.
 
# References and Disclaimer
  Usage of php code mainly and highly references the HKDSE ICT elective B textbook.
  
  All codes and commands in the report are original.
  
  The ER diagram of ecams was created via the editing application Paint in 2026 by Chen Ka Ying for HKDSE ICT SBA usage only.
  
  All screenshots of the SQL demonstration code is original and created using both the XAMPP MySQL module on the web browser Chrome and Visual Code Studio.
  
  All screenshots of the code of the dynamic webpage is original and created via the application Visual Studio Code.
  
  All data inserted into the SQL tables are made-up and contain no FULL real life references.
  
  All screenshots of the dynamic webpage included in this report is executed in the web browser Chrome with XAMPP.
  
  All codes used will be uploaded to a GitHub repository.
  
  The repository is currently set as private to ensure fairness of the grading system. It will be set as public one week after the deadline (7 days after 8.28.2026, which is 4.9.2026) and will be set private again after the marking of my SBA piece. 
  
  The link of this repository is only shared in this report for the examiner and nowhere else. My ICT subject teacher marking this SBA and markers of from the HKEAA should be the only people seeing the files in the repository. 
  
