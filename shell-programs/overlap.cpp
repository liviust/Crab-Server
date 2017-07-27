#include <iostream>
#include <fstream>
#include <stdlib.h>
#include <string>
#include <cstring>
#include <vector>
#include <algorithm>  
using namespace std;
int main(int argc,char *argv[]){
    
    char str;
	bool notEmpty=false; //Write out to file if True
		
	// g++ overlap.cpp -o overlap
	// ./overlap sort_file out_file

    FILE *fp1 = fopen(argv[1],"r"); //sort_file
    FILE *fp2 = fopen(argv[2],"w"); //out_file
	
    
    char queryId[100] = {0}; 	//$1*
    char subjectId[30] = {0}; 	//$2
    float identity = 0.0; 		//$3
    int alignmentLength = 0; 	//$4
    int mismatches = 0; 		//$5
    int gapOpens = 0; 			//$6
    int queryStart = 0; 		//$7*(q. start)
    int queryEnd = 0; 			//$8*(q. end)
    int subjectStart = 0; 		//$9(s. start)
    int subjectEnd = 0; 		//$10(s. end)
    char evalue[20] = {0}; 		//$11
    char bitScore[20] = {0}; 	//$12(int/float)
	char orientation[2] = {0}; 	//$13
	
    int newlength = 0;
    int nowlength = 0;
    
	//now是上一個，然後跟新讀進來的比較
	
    char nowQueryId[100] = {0};
    char nowSubjectId[30] = {0};
    float nowIdentity = 0.0;
    int nowAlignmentLength = 0;
    int nowMismatches = 0;
    int nowGapOpens = 0;
    int nowQPosStart = 0;
    int nowQPosEnd = 0;
    int nowSPosStart = 0;
    int nowSPosEnd = 0;
    char nowEvalue[20] = {0};
    char nowBitScore[20] = {0};
	char noworientation[2] = {0};
	
    bool flags = false;
    
    while(fscanf(fp1,"%s %s %f %d %d %d %d %d %d %d %s %s %s",
                queryId, subjectId, &identity, &alignmentLength, &mismatches, &gapOpens,
                &queryStart, &queryEnd, &subjectStart, &subjectEnd, evalue, bitScore, orientation) != EOF)
    {        
        if(strcmp(nowQueryId,queryId) != 0) //nowQueryId!=queryId
        {
            // scaffold00001 -> scaffold00002
			if(flags == true)
			//out_file
            fprintf(fp2,"%s\t%s\t%f\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%s\t%s\t%s\n",
                    nowQueryId, nowSubjectId, nowIdentity, nowAlignmentLength, nowMismatches, nowGapOpens,
                    nowQPosStart, nowQPosEnd, nowSPosStart, nowSPosEnd, nowEvalue, nowBitScore, noworientation);
            
			strcpy(nowQueryId,queryId); //%s
            strcpy(nowSubjectId,subjectId);
            nowIdentity = identity; //%f
            nowAlignmentLength = alignmentLength; //%d
            nowMismatches = mismatches;
            nowGapOpens = gapOpens;
            nowQPosStart = queryStart;
            nowQPosEnd = queryEnd;
            nowSPosStart = subjectStart;
            nowSPosEnd = subjectEnd; 
            strcpy(nowEvalue,evalue);
            strcpy(nowBitScore,bitScore);
			strcpy(noworientation,orientation);
            
			nowlength = nowQPosEnd - nowQPosStart + 1;
			flags = true;
        }        
        else //nowQueryId==queryId
        {
            if(queryStart > nowQPosEnd) //$7>$8
            {                
                /*
					比較第七欄(起點)是否大於上一個的第八欄(終點)
					大於的話代表沒重疊				
				*/
				
				fprintf(fp2,"%s\t%s\t%f\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%s\t%s\t%s\n",
                    nowQueryId, nowSubjectId, nowIdentity, nowAlignmentLength, nowMismatches, nowGapOpens,
                    nowQPosStart, nowQPosEnd, nowSPosStart, nowSPosEnd, nowEvalue, nowBitScore, noworientation);
                strcpy(nowQueryId,queryId);
                strcpy(nowSubjectId,subjectId);
                nowIdentity = identity;
                nowAlignmentLength = alignmentLength;
                nowMismatches = mismatches;
                nowGapOpens = gapOpens;
                nowQPosStart = queryStart;
                nowQPosEnd = queryEnd;
                nowSPosStart = subjectStart;
                nowSPosEnd = subjectEnd;                
                strcpy(nowEvalue,evalue);
                strcpy(nowBitScore,bitScore);
				strcpy(noworientation,orientation);
                
                nowlength = nowQPosEnd - nowQPosStart + 1;
            }
            else //比較第七欄到第八欄的距離
            {
                newlength = queryEnd - queryStart + 1;
                if(newlength == nowlength) //保留
                {
                    fprintf(fp2,"%s\t%s\t%f\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%s\t%s\t%s\n",
                        nowQueryId, nowSubjectId, nowIdentity, nowAlignmentLength, nowMismatches, nowGapOpens,
                        nowQPosStart, nowQPosEnd, nowSPosStart, nowSPosEnd, nowEvalue, nowBitScore, noworientation);
                    strcpy(nowQueryId,queryId);
                    strcpy(nowSubjectId,subjectId);
                    nowIdentity = identity;
                    nowAlignmentLength = alignmentLength;
                    nowMismatches = mismatches;
                    nowGapOpens = gapOpens;
                    nowQPosStart = queryStart;
                    nowQPosEnd = queryEnd;
                    nowSPosStart = subjectStart;
                    nowSPosEnd = subjectEnd; 
                    strcpy(nowEvalue,evalue);
                    strcpy(nowBitScore,bitScore);
					strcpy(noworientation,orientation);
                    
                    nowlength = nowQPosEnd - nowQPosStart + 1;
                }
                if(newlength > nowlength) //取代
                {
                    strcpy(nowQueryId,queryId);
                    strcpy(nowSubjectId,subjectId);
                    nowIdentity = identity;
                    nowAlignmentLength = alignmentLength;
                    nowMismatches = mismatches;
                    nowGapOpens = gapOpens;
                    nowQPosStart = queryStart;
                    nowQPosEnd = queryEnd;
                    nowSPosStart = subjectStart;
                    nowSPosEnd = subjectEnd; 
                    strcpy(nowEvalue,evalue);
                    strcpy(nowBitScore,bitScore);
					strcpy(noworientation,orientation);
                    
                    nowlength = nowQPosEnd - nowQPosStart + 1;
                }
            }
        }
    }    //end of file          
            fprintf(fp2,"%s\t%s\t%f\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%s\t%s\t%s\n",
                nowQueryId, nowSubjectId, nowIdentity, nowAlignmentLength, nowMismatches, nowGapOpens,
                nowQPosStart, nowQPosEnd, nowSPosStart, nowSPosEnd, nowEvalue, nowBitScore, noworientation);  
     
    
    fclose(fp1);
    fclose(fp2);
	return 0;
}

