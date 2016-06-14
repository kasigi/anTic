
    
    

#Selects Permissions at the table Level
SELECT 
	IF(sum(PMU.`read`)>=1,1,0) as `readG`, 
    IF(sum(PMU.`write`)>=1,1,0) as `writeG`,
    IF(sum(PMU.`execute`)>=1,1,0) as `executeG`,
    IF(sum(PMU.administer)>=1,1,0) as `administerG` 
FROM anticPermission PMU
LEFT JOIN anticUserGroup UP 
	ON (UP.groupID = PMU.groupID 
		AND UP.userID = 2)
    AND (PMU.pkArrayBaseJSON IS NULL OR PMU.pkArrayBaseJSON = "")
    WHERE 
		PMU.tableName = "task"
        AND (PMU.pkArrayBaseJSON IS NULL OR PMU.pkArrayBaseJSON = "")
        AND (UP.groupID IS NOT NULL
			OR PMU.userID = 2)
;

#Selects permissions at the record level
SELECT 
	T.*, 
    IF(sum(PMU.`read`)>=1,1,0) as `read`, 
    IF(sum(PMU.`write`)>=1,1,0) as `write`,
    IF(sum(PMU.`execute`)>=1,1,0) as `execute`,
    IF(sum(PMU.administer)>=1,1,0) as `administer` 
FROM anticPermission PMU
INNER JOIN anticUserGroup UG 
	ON (UG.groupID = PMU.groupID AND UG.userID=2) 
		OR ((PMU.groupID IS NULL OR PMU.groupID="") AND PMU.userID = 2 )
INNER JOIN task T ON 
	PMU.pkArrayBaseJSON = CONCAT( '{"taskID":"',T.taskID,'","title":"',T.title,'","projectTypeID":"',T.projectTypeID,'","status":"',T.`status`,'","person":"',T.person,'","description":"',T.description,'"}')
GROUP BY PMU.pkArrayBaseJSON
;


# Selects all records from table and joins permissions
SELECT 
	T2.*, #Regular keys to return
	IF(readR>readT,readR,readT) as `anticRead`,
    IF(writeR>writeT,writeR,writeT) as `anticWrite`,
    IF(executeR>executeT,executeR,executeT) as `anticExecute`,
    IF(administerR>administerT,administerR,administerT) as `anticAdminister`
    FROM task T2 #Desired data table
#Join in the record-level permissions
LEFT JOIN (SELECT 
	T.taskID, # All Primary Keys
    IF(sum(PMU.`read`)>=1,1,0) as `readR`, 
    IF(sum(PMU.`write`)>=1,1,0) as `writeR`,
    IF(sum(PMU.`execute`)>=1,1,0) as `executeR`,
    IF(sum(PMU.administer)>=1,1,0) as `administerR` 
FROM anticPermission PMU
INNER JOIN anticUserGroup UG 
	ON (UG.groupID = PMU.groupID AND UG.userID=2) 
		OR ((PMU.groupID IS NULL OR PMU.groupID="") AND PMU.userID = 2 )
INNER JOIN task T ON 
	PMU.pkArrayBaseJSON = CONCAT( '{"taskID":"',T.taskID,'","title":"',T.title,'","projectTypeID":"',T.projectTypeID,'","status":"',T.`status`,'","person":"',T.person,'","description":"',T.description,'"}')
GROUP BY PMU.pkArrayBaseJSON) AS PMRec ON PMRec.taskID = T2.taskID #Join on All Primary Keys
#Join in the Table Level Permissions
LEFT JOIN 
		(SELECT IF(sum(PMU.`read`)>=1,1,0) as `readT`, 
				IF(sum(PMU.`write`)>=1,1,0) as `writeT`,
				IF(sum(PMU.`execute`)>=1,1,0) as `executeT`,
                IF(sum(PMU.`administer`)>=1,1,0) as `administerT` 
			FROM anticPermission PMU
		LEFT JOIN anticUserGroup UP 
			ON (UP.groupID = PMU.groupID 
			AND UP.userID = 2)
			AND (PMU.pkArrayBaseJSON IS NULL OR PMU.pkArrayBaseJSON = "")
		WHERE 
			PMU.tableName = "task"
			AND (PMU.pkArrayBaseJSON IS NULL OR PMU.pkArrayBaseJSON = "")
			AND (UP.groupID IS NOT NULL
			OR PMU.userID = 2)
		) AS PMUT ON 1=1
HAVING anticRead =1;

