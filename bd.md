127.0.0.1:3306/u328419981_inscrip_cbmaex/COLUMNS/		https://auth-db1527.hstgr.io/index.php?route=/database/sql&db=u328419981_inscrip_cbmaex

   Mostrando filas 0 - 24 (total de 843, La consulta tardó 0.0037 segundos.)


SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'u328419981_inscrip_cbmaex';


TABLE_NAME	COLUMN_NAME	COLUMN_TYPE	IS_NULLABLE	COLUMN_KEY	EXTRA	
deportes	id	int(11)	NO	PRI	auto_increment	
deportes	deporte	varchar(100)	NO			
deportes	estado	enum('Activo','Inactivo','','')	NO			
cursos_2025	ID_Curso	varchar(20)	NO	PRI		
cursos_2025	Estado_del_curso	enum('ACTIVO','INACTIVO','','')	YES			
cursos_2025	Cc	varchar(20)	YES			
cursos_2025	Nombre_del_curso	varchar(200)	YES			
cursos_2025	Codigo_Facturacion	varchar(5)	YES			
cursos_2025	Sede	varchar(8)	YES			
cursos_2025	Tarifa_Curso	varchar(12)	YES			
cursos_2025	Linea	varchar(20)	YES			
cursos_2025	Actividad	int(12)	YES	MUL		
cursos_2025	Docente	varchar(10)	YES			
cursos_2025	Cupos_minimos	varchar(10)	YES			
cursos_2025	Cupos_maximos	varchar(3)	YES			
cursos_2025	Fecha_Inicio	date	YES			
cursos_2025	Fecha_Final	date	YES			
cursos_2025	Descripción	varchar(531)	YES			
cursos_2025	Fecha_inicial_edad	varchar(10)	YES			
cursos_2025	Fecha_final_edad	varchar(10)	YES			
cursos_2025	Corporacion	varchar(20)	YES			
cursos_2025	Tipo	varchar(20)	YES			
cursos_2025	Lunes	varchar(20)	YES			
cursos_2025	Martes	varchar(20)	YES			
cursos_2025	Miércoles	varchar(20)	YES			
