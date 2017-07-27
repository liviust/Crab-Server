#!/bin/bash
#Shell variables
filename=$1
AssemblyPrefix=$2
types_of_analysis=$3
target_file=$4
ncbi_cutoff=$5
genomeSize=$6
vfdb_cutoff=$7
vfdb_threshold_id=$8
vfdb_min_length=$9
card_cutoff=${10}
card_threshold_id=${11}
card_min_length=${12}
google_id=${13}
job_id=${14}
email=${15}
googlename=${16}

#create a new storage folder
mkdir ${filename}/quast-reports
mkdir ${filename}/ncbi-reports
mkdir ${filename}/vfdb-reports
mkdir ${filename}/card-reports
path_to_quast_output="/bip7_disk/WWW/WWW/www/Crab/${filename}/quast-reports"
path_to_ncbi_output="/bip7_disk/WWW/WWW/www/Crab/${filename}/ncbi-reports"
path_to_vfdb_output="/bip7_disk/WWW/WWW/www/Crab/${filename}/vfdb-reports"
path_to_card_output="/bip7_disk/WWW/WWW/www/Crab/${filename}/card-reports"
#echo $path_to_quast_output

#Execute mongo commands through shell scripts
mongo --nodb --quiet --eval "var user_id='"${google_id}"', job_id='"${job_id}"';" /bip7_disk/WWW/WWW/www/Crab/Model/mongo_shell/mongoscript_canu.js
date "+TIME: %H:%M:%S"
#Assembling
/bip7_disk/WWW/WWW/www/Crab/Model/canu/Linux-amd64/bin/canu -d ${filename}/ -p ${AssemblyPrefix} gnuplot=/usr/bin/gnuplot genomeSize=$genomeSize useGrid=false maxThreads=10 ${types_of_analysis} ${target_file}
date "+TIME: %H:%M:%S"
#The assembled contigs
contigs="/bip7_disk/WWW/WWW/www/Crab/${filename}/*.contigs.fasta"
#echo $contigs

#Zipping contigs
cd $path_to_quast_output/..
zip ${AssemblyPrefix}.contigs.zip ${AssemblyPrefix}.contigs.fasta

#QUAST
/bip7_disk/WWW/WWW/www/Crab/Model/QUAST/quast-4.5/quast.py $contigs -o $path_to_quast_output

#Zipping QUAST Files
cd $path_to_quast_output/..
zip -r quast-reports quast-reports/*

#Execute mongo commands through shell scripts
mongo --nodb --quiet --eval "var user_id='"${google_id}"', job_id='"${job_id}"';" /bip7_disk/WWW/WWW/www/Crab/Model/mongo_shell/mongoscript_species.js

#Extract partial sequence:1-100000
samtools faidx $contigs $(cat $contigs | /bip7_disk/WWW/WWW/www/Crab/Model/bioawk/bioawk -c fastx '{ print length($seq), $name }' | sort -k1,1rn | head -1 | cut -f 2):1-100000 > $path_to_ncbi_output/contigs.draft

#species identification
/bip7_disk/WWW/WWW/www/Crab/Model/ncbi-blast-2.6.0+/bin/blastn -task blastn -db /bip7_disk/WWW/WWW/www/Crab/Model/nt/nt -query $path_to_ncbi_output/contigs.draft -gilist /bip7_disk/WWW/WWW/www/Crab/Model/sequence_gi/bacteria.nt.gi.min.txt -evalue ${ncbi_cutoff} -outfmt 6 -out $path_to_ncbi_output/species.tsv -num_threads 10

#Generate a species report
sort -k2,2 -k12,12nr $path_to_ncbi_output/species.tsv | awk '{if(! a[$2]){print; a[$2]++}}' > $path_to_ncbi_output/species.draft && sort -k12,12nr $path_to_ncbi_output/species.draft | sed -n 1,5p | cut -f 1,2,3,12 | awk '{print $2"\t"$1"\t"$3"\t"$4;}' > $path_to_ncbi_output/species.report

#Extracting information from a SeqRecord
ln -s /bip7_disk/WWW/WWW/www/Crab/Model/Biopython_v1.69/SeqRecord.py $path_to_ncbi_output/
ln -s /bip7_disk/WWW/WWW/www/Crab/Model/Biopython_v1.69/SeqRecord_gbk.py $path_to_ncbi_output/

cat $path_to_ncbi_output/species.report | cut -f 1 > $path_to_ncbi_output/GenBank_Top5
cd $path_to_ncbi_output/

#Organism
python SeqRecord.py | awk '{print $0"\""}' | sed 's/^/\"/' > $path_to_ncbi_output/GenBank_Top5.source

#BioProject
python SeqRecord_gbk.py > $path_to_ncbi_output/Biopython_Top5
cat $path_to_ncbi_output/Biopython_Top5 | awk '{gsub("\x27",""); print}' | sed 's/[][]//g' | sed -n 1~2p > $path_to_ncbi_output/BioProject_Top5.source

#BioSample
cat $path_to_ncbi_output/Biopython_Top5 | awk '{gsub("\x27",""); print}' | sed 's/[][]//g' | sed -n 2~2p > $path_to_ncbi_output/BioSample_Top5.source

#Eexport data to CSV
paste $path_to_ncbi_output/GenBank_Top5.source $path_to_ncbi_output/species.report $path_to_ncbi_output/BioProject_Top5.source $path_to_ncbi_output/BioSample_Top5.source | sed 's/\t/,/g' > $path_to_ncbi_output/ncbi_top5_species.csv

#Find files with specific extensions (<> .fai)
queryInput=$(find $contigs | sed 's/.fai//g' | awk 'NR==1{print}')
echo "Find files with specific extensions: "$queryInput

#Execute mongo commands through shell scripts
mongo --nodb --quiet --eval "var user_id='"${google_id}"', job_id='"${job_id}"';" /bip7_disk/WWW/WWW/www/Crab/Model/mongo_shell/mongoscript_VFs.js

#Virulence
/bip7_disk/WWW/WWW/www/Crab/Model/ncbi-blast-2.6.0+/bin/blastx -query $queryInput -db /bip7_disk/WWW/WWW/www/Crab/Model/VFDB_v170317/db_setB -evalue ${vfdb_cutoff} -outfmt 6 -out $path_to_vfdb_output/virulence.tsv -num_threads 10

vfdbNonFilter=$(cat $path_to_vfdb_output/virulence.tsv | wc -l)
echo "--Non filter blast results of VFDB: "${vfdbNonFilter}

#Trim the complete overlapping elements
sort -k1,1 -k12,12nr $path_to_vfdb_output/virulence.tsv | awk '{if ($8>$7)print $0"\t+"; else print$1"\t"$2"\t"$3"\t"$4"\t"$5"\t"$6"\t"$8"\t"$7"\t"$9"\t"$10"\t"$11"\t"$12"\t-";}'| awk '{if(!arr[$1]){print;arr[$7$8]++;arr[$1]++;} if(!arr[$7$8]){print; arr[$7$8]++;}}' | sort -k1,1 -k7,7n > $path_to_vfdb_output/vfdb.sort.draft

vfdbCompleteOverlappingFilter=$(cat $path_to_vfdb_output/vfdb.sort.draft | wc -l)
echo "--Complete overlapping filter blast results of VFDB: "${vfdbCompleteOverlappingFilter}

#Virulence coordinate filter
/bip7_disk/WWW/WWW/www/Crab/Model/programs/overlap $path_to_vfdb_output/vfdb.sort.draft $path_to_vfdb_output/vfdb.trim.draft

vfdbCoordinateFilter=$(cat $path_to_vfdb_output/vfdb.trim.draft | wc -l)
echo "--Virulence coordinate filter blast results of VFDB: "${vfdbCoordinateFilter}

#Reserved unique virulence factors in the same contig
sort -k1,1 -k2,2 -k12,12nr $path_to_vfdb_output/vfdb.trim.draft | awk '{if(! arr[$1$2]){print; arr[$1$2]++;}}' > $path_to_vfdb_output/vfdb.unique.draft

vfdbUniqueFilter=$(cat $path_to_vfdb_output/vfdb.unique.draft | wc -l)
echo "--Reserved unique virulence factors in the same contig: "${vfdbUniqueFilter}

#An AWK array joining-tables
awk 'BEGIN {FS=OFS="\t"} NR==FNR {a[$1]=$2"\t"$1"\t"$3"\t"$4"\t"$5"\t"$6;next} {print $1,a[$2],$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13}' /bip7_disk/WWW/WWW/www/Crab/Model/VFDB_v170317/VFs_header/VFs_comprehensive $path_to_vfdb_output/vfdb.unique.draft > $path_to_vfdb_output/VFDB-join-results

#Sequence completeness between the query and subject sequences (VFCoverage)
sed 's/ /%/g' $path_to_vfdb_output/VFDB-join-results | awk '{print $0"\t"$9/$2*100;}' > $path_to_vfdb_output/VFDB-coverage-results

#Select threshold for %ID (%Identity) and Select minimum length (%VFCoverage)
awk '{if(($8>'$vfdb_threshold_id') && ($19>'$vfdb_min_length')){print $0;}}' $path_to_vfdb_output/VFDB-coverage-results > $path_to_vfdb_output/VFDB-percentage-results

vfdbthresholdFilter=$(cat $path_to_vfdb_output/VFDB-percentage-results | wc -l)
echo "--Select threshold for identity ("${vfdb_threshold_id}"%) and minimum length ("${vfdb_min_length}"%): "${vfdbthresholdFilter}

#VFDB HTML form
awk '{print $4"\t"$8"\t"$19"\t"$1"\t"$12".."$13"\t"$5"\t"$3;}' $path_to_vfdb_output/VFDB-percentage-results | sed 's/%/ /g' | sed 's/\t/,/g' > $path_to_vfdb_output/VFDB-blast-html.csv

#Add a header to a VFDB tab delimited file and Eexport data to CSV
echo $'Contig\tVFLength\tVFID\tVFgene\tVFfunction\tVFfeature\tOrganism\tIdentity\tAlignLength\tMismatch\tGapOpen\tContigStart\tContigEnd\tVFDBStart\tVFDBEnd\tE-value\tBitScore\tOrientation\tVFCoverage' | cat - $path_to_vfdb_output/VFDB-coverage-results | sed 's/\t/,/g'| sed 's/%/ /g' > $path_to_vfdb_output/VFDB-blast-results.csv

#Make a Partition Layout
#Retrieve column schema information (VF_Name/VF_Structure)
cat $path_to_vfdb_output/VFDB-percentage-results | cut -f 4 > $path_to_vfdb_output/VF_Name
cat $path_to_vfdb_output/VFDB-percentage-results | cut -f 6 | sed 's/%/ /g' | sed '/(/, $ s//\t/g' | cut -f 1 | sed 's/"//g' | awk '{print $0"\""}' | sed 's/^/\"/' | sed 's/ "/"/g' | sed 's/ /_/g' > $path_to_vfdb_output/VF_Structure

#JOIN
awk 'FNR==NR{a[$1]=$2 FS $3;next}{ print $0, a[$1]}' /bip7_disk/WWW/WWW/www/Crab/Model/VFDB_v170317/VFs_description_file/VFs_retrieve.tsv $path_to_vfdb_output/VF_Structure | sed 's/" "/"\t"/g' | awk 'BEGIN {FS="\t"} {if($2!=""){print $0} else if($2=="") {print $0"\tOthers"}}' > $path_to_vfdb_output/VF_Keyword

#Layer retrieve
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($2!=""){print $1,$2}}' > $path_to_vfdb_output/1to1
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($3!=""){print $1,$3}}' > $path_to_vfdb_output/1to2 
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($4!=""){print $1,$4}}' > $path_to_vfdb_output/1to3 
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($5!=""){print $1,$5}}' > $path_to_vfdb_output/1to4
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($6!=""){print $1,$6}}' > $path_to_vfdb_output/1to5
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($7!=""){print $1,$7}}' > $path_to_vfdb_output/1to6
paste $path_to_vfdb_output/VF_Name $path_to_vfdb_output/VF_Keyword | cut -f 1,3 | sed 's/;_/"\t"/g' | awk 'BEGIN {FS="\t"} {if($8!=""){print $1,$8}}' > $path_to_vfdb_output/1to7

#Required format for D3 partition plugin
cat $path_to_vfdb_output/1to1 $path_to_vfdb_output/1to2 $path_to_vfdb_output/1to3 $path_to_vfdb_output/1to4 $path_to_vfdb_output/1to5 $path_to_vfdb_output/1to6 $path_to_vfdb_output/1to7 | sed 's/ /\t/g' | sort -k2,2 | awk -F'\t' -v OFS='\t' 'NR>=1{sub(/_/,"%", $1)} 1' | sed 's/\t/,/g' | sed 's/",/"/g' | sed 's/_/ /g' | sed 's/%/_/g' > $path_to_vfdb_output/flare.tmp

#Put a header in file
awk 'BEGIN{print "Name,Category"}1' $path_to_vfdb_output/flare.tmp > $path_to_vfdb_output/flare.csv

#Deleting temporary files
rm -f $path_to_vfdb_output/1to1 $path_to_vfdb_output/1to2 $path_to_vfdb_output/1to3 $path_to_vfdb_output/1to4 $path_to_vfdb_output/1to5 $path_to_vfdb_output/1to6 $path_to_vfdb_output/1to7

#Execute mongo commands through shell scripts
mongo --nodb --quiet --eval "var user_id='"${google_id}"', job_id='"${job_id}"';" /bip7_disk/WWW/WWW/www/Crab/Model/mongo_shell/mongoscript_AMR.js

#Resistance
/bip7_disk/WWW/WWW/www/Crab/Model/ncbi-blast-2.6.0+/bin/blastx -query $queryInput -db /bip7_disk/WWW/WWW/www/Crab/Model/CARD_170306/db_card -evalue ${card_cutoff} -outfmt 6 -out $path_to_card_output/AMR.tsv -num_threads 10

cardNonFilter=$(cat $path_to_card_output/AMR.tsv | wc -l)
echo "--Non filter blast results of CARD: "${cardNonFilter}

#Trim the complete overlapping elements
sort -k1,1 -k12,12nr $path_to_card_output/AMR.tsv | awk '{if ($8>$7)print $0"\t+"; else print$1"\t"$2"\t"$3"\t"$4"\t"$5"\t"$6"\t"$8"\t"$7"\t"$9"\t"$10"\t"$11"\t"$12"\t-";}'| awk '{if(!arr[$1]){print;arr[$7$8]++;arr[$1]++;} if(!arr[$7$8]){print; arr[$7$8]++;}}' | sort -k1,1 -k7,7n > $path_to_card_output/card.sort.draft

cardCompleteOverlappingFilter=$(cat $path_to_card_output/card.sort.draft | wc -l)
echo "--Complete overlapping filter blast results of CARD: "${cardCompleteOverlappingFilter}

#AMR coordinate filter
/bip7_disk/WWW/WWW/www/Crab/Model/programs/overlap $path_to_card_output/card.sort.draft $path_to_card_output/card.trim.draft

cardCoordinateFilter=$(cat $path_to_card_output/card.trim.draft | wc -l)
echo "--AMR coordinate filter blast results of CARD: "${cardCoordinateFilter}

#Reserved unique resistomes in the same contig
sort -k1,1 -k2,2 -k12,12nr $path_to_card_output/card.trim.draft | awk '{if(! arr[$1$2]){print; arr[$1$2]++;}}' > $path_to_card_output/card.unique.draft

cardUniqueFilter=$(cat $path_to_card_output/card.unique.draft | wc -l)
echo "--Reserved unique resistomes in the same contig: "${cardUniqueFilter}

#An AWK array joining-tables (for filter)
awk 'BEGIN {FS=OFS="\t"} NR==FNR {a[$3]=$2"\t"$1"\t"$3"\t"$4;next} {print $1,a[$2],$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13}' /bip7_disk/WWW/WWW/www/Crab/Model/CARD_170306/CARD_retrieve/CARD_ARO_CVlen_link $path_to_card_output/card.unique.draft > $path_to_card_output/card.join.filter.draft

#Sequence completeness between the query and subject sequences (AMRCoverage)
sed 's/ /%/g' $path_to_card_output/card.join.filter.draft | awk '{print $0"\t"$7/$5*100;}' > $path_to_card_output/AMR-coverage-results

#Select threshold for %ID (%Identity) and Select minimum length (%AMRCoverage)
awk '{if(($6>50) && ($17>50)){print $0;}}' $path_to_card_output/AMR-coverage-results > $path_to_card_output/AMR-percentage-results

cardthresholdFilter=$(cat $path_to_card_output/AMR-percentage-results | wc -l)
echo "--Select threshold for identity ("${card_threshold_id}"%) and minimum length ("${card_min_length}"%): "${cardthresholdFilter}

#An AWK array joining-tables (for Zoomable Sunburst)
awk 'BEGIN {FS=OFS="\t"} NR==FNR {a[$2]=$3"\t"$1"\t"$4"\t"$2;next} {print $1,$2,a[$3],$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17}' /bip7_disk/WWW/WWW/www/Crab/Model/CARD_170306/CARD_retrieve/new_card0515.tsv $path_to_card_output/AMR-percentage-results > $path_to_card_output/card.join.sunburst.draft.mass

#Eexport data to CSV
cat $path_to_card_output/card.join.sunburst.draft.mass | cut -f 3,4,5 | sed 's/ /%/g' | awk '{print $1"\t"$3"\t"$2"\t"1;}' | sed 's/%/ /g' | sort -k1,1 -k2,2 | sed 's/\t/,/g' > $path_to_card_output/INPUTFILE.csv

#Python csv to nested JSON (INPUTFILE.json)
python /bip7_disk/WWW/WWW/www/Crab/Model/programs/csvtojson.py -q $path_to_card_output/INPUTFILE.csv

#AMR HTML form
cat $path_to_card_output/card.join.sunburst.draft.mass | cut -f 4,9,20,1,13,14,3,6,2 | sed 's/ /%/g' | awk '{print $4"\t"$6"\t"$9"\t"$1"\t"$7".."$8"\t"$3"\t"$5"\t"$2}' | sed 's/%/ /g' | sed 's/\t/,/g' > $path_to_card_output/AMR-blast-html.csv

#Add a header to a CARD tab delimited file and Eexport data to CSV
cat $path_to_card_output/card.join.sunburst.draft.mass | sed 's/ /%/g' | awk '{print $1"\t"$8"\t"$6"\t"$4"\t"$9"\t"$10"\t"$11"\t"$12"\t"$13"\t"$14"\t"$15"\t"$16"\t"$17"\t"$18"\t"$19"\t"$20"\t"$3"\t"$5"\thttps://card.mcmaster.ca/ontology/"$2}' | sed 's/\t/,/g' | sed 's/%/ /g' > $path_to_card_output/AMR-coverage-results-merge

echo $'Contig\tAMRLength\tAROID\tAMRgene\tIdentity\tAlignLength\tMismatch\tGapOpen\tContigStart\tContigEnd\tAMRDBStart\tAMRDBEnd\tE-value\tBitScore\tOrientation\tAMRCoverage\tPredicted%Phenotype\tAntibiotic%Agent\tOntology' | cat - $path_to_card_output/AMR-coverage-results-merge | sed 's/\t/,/g'| sed 's/%/ /g' > $path_to_card_output/CARD-blast-results.csv

#Execute mongo commands through shell scripts
mongo --nodb --quiet --eval "var user_id='"${google_id}"', job_id='"${job_id}"';" /bip7_disk/WWW/WWW/www/Crab/Model/mongo_shell/mongoscript.js

#My job finished
#php-cgi -f /bip7_disk/WWW/WWW/www/Crab/cgi-php.php job_id=${job_id} email=${email} googlename=${googlename}