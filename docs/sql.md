# Smazání duplicitních tracků

DELETE t1
FROM track t1
INNER JOIN track t2
ON t1.track_name_artist_name_hash = t2.track_name_artist_name_hash
AND (
t1.release_year > t2.release_year
OR (t1.release_year = t2.release_year AND t1.id > t2.id)
OR (t1.release_year IS NULL AND t2.release_year IS NOT NULL)
);