#!/bin/bash
set -eu
# thanks to Thomas Mellenthin @ https://github.com/melle/l33tport/blob/master/rrdtool/graphRRDBs.sh
DSLDB=/home/rrd/rrd/dsl.rrd
LTEDB=/home/rrd/rrd/lteinfo.rrd
OUTPUTDIR=/var/www/stats

# Pleasant colors from
# https://oss.oetiker.ch/rrdtool-trac/wiki/OutlinedAreaGraph
#
#          LIGHT   DARK
# RED     #EA644A #CC3118
# ORANGE  #EC9D48 #CC7016
# YELLOW  #ECD748 #C9B215
# GREEN   #54EC48 #24BC14
# BLUE    #48C4EC #1598C3
# PINK    #DE48EC #B415C7
# PURPLE  #7648EC #4D18E4



# Graphs DSL values. The scaling is pretty bogus.
#
# $1 - filenme
# $2 - title
# $3 - starttime
# $4 - endtime
function drawDSL {
	rm -f $OUTPUTDIR/$1

	rrdtool graph $OUTPUTDIR/$1 \
		-s $3 \
		-e $4 \
		-t "$2" \
		-h 200 \
		-w 600 \
		-a PNG \
		-v "Upstream / Downstream" \
		--upper-limit 16000 --rigid \
		DEF:dactual=$DSLDB:dactual:AVERAGE \
		DEF:dattainable=$DSLDB:dattainable:AVERAGE \
		DEF:uactual=$DSLDB:uactual:AVERAGE \
		DEF:uattainable=$DSLDB:uattainable:AVERAGE \
		DEF:uLine=$DSLDB:uLine:AVERAGE \
		DEF:dLine=$DSLDB:dLine:AVERAGE \
		DEF:uSNR=$DSLDB:uSNR:AVERAGE \
		DEF:dSNR=$DSLDB:dSNR:AVERAGE \
		DEF:dHEC=$DSLDB:dHEC:MAX \
		DEF:dCRC=$DSLDB:dCRC:MAX \
		CDEF:dCRCScaled=dCRC,20000,* \
		CDEF:dHECScaled=dHEC,20000,* \
		CDEF:uLineScaled=uLine,10,* \
		CDEF:dLineScaled=dLine,10,* \
		CDEF:uSNRScaled=uSNR,80,* \
		CDEF:dSNRScaled=dSNR,80,* \
		CDEF:dHECOutline=dHECScaled,dCRCScaled,dHECScaled,+,UNKN,IF \
		\
		COMMENT:" \t\t\t" \
		COMMENT:"Cur\: \t\t" \
		COMMENT:"Min\: \t\t" \
		COMMENT:"Avg\: \t\t" \
		COMMENT:"Max\: \n" \
		\
		AREA:dattainable#54EC48CC \
		LINE1:dattainable#24BC14:"dattainable\t\t"  \
		GPRINT:dattainable:LAST:"%1.0lf kBps\t" \
		GPRINT:dattainable:MIN:"%1.0lf kBps\t" \
		GPRINT:dattainable:AVERAGE:"%1.0lf kBps\t" \
		GPRINT:dattainable:MAX:"%1.0lf kBps\n" \
		\
		LINE1:dactual#ff3535:"dactual\t\t" \
		GPRINT:dactual:LAST:"%1.0lf kBps\t" \
		GPRINT:dactual:MIN:"%1.0lf kBps\t" \
		GPRINT:dactual:AVERAGE:"%1.0lf kBps\t" \
		GPRINT:dactual:MAX:"%1.0lf kBps\n" \
		\
		AREA:uattainable#48C4EC \
		LINE1:uattainable#1598C3:"uattainable\t\t" \
		GPRINT:uattainable:LAST:"%1.0lf kBps\t" \
		GPRINT:uattainable:MIN:"%1.0lf kBps\t" \
		GPRINT:uattainable:AVERAGE:"%1.0lf kBps\t" \
		GPRINT:uattainable:MAX:"%1.0lf kBps\n" \
		\
		LINE1:uactual#0000FF:"uactual\t\t"  \
		GPRINT:uactual:LAST:"%1.0lf kBps\t" \
		GPRINT:uactual:MIN:"%1.0lf kBps\t" \
		GPRINT:uactual:AVERAGE:"%1.0lf kBps\t" \
		GPRINT:uactual:MAX:"%1.0lf kBps\n" \
		\
		AREA:dHECScaled#EC9D48AA:"Header Errors\t" \
		GPRINT:dHEC:LAST:"%1.0lf \t\t" \
		GPRINT:dHEC:MIN:"%1.0lf \t\t" \
		GPRINT:dHEC:AVERAGE:"%1.0lf \t\t" \
		GPRINT:dHEC:MAX:"%1.0lf \n" \
		\
		AREA:dCRCScaled#ECD748AA:"CRC Errors\t\t":STACK \
		LINE1:dHECScaled#CC7016AA  \
		LINE1:dHECOutline#C9B215AA \
		GPRINT:dCRC:LAST:"%1.0lf \t\t" \
		GPRINT:dCRC:MIN:"%1.0lf \t\t" \
		GPRINT:dCRC:AVERAGE:"%1.0lf \t\t" \
		GPRINT:dCRC:MAX:"%1.0lf \n" \
		\
		LINE1:uSNRScaled#DE48EC:"uSNR\t\t\t" \
		GPRINT:uSNR:LAST:"%1.0lf \t\t" \
		GPRINT:uSNR:MIN:"%1.0lf \t\t" \
		GPRINT:uSNR:AVERAGE:"%1.0lf \t\t" \
		GPRINT:uSNR:MAX:"%1.0lf \n" \
		\
		LINE1:dSNRScaled#B415C7:"dSNR\t\t\t" \
		GPRINT:dSNR:LAST:"%1.0lf \t\t" \
		GPRINT:dSNR:MIN:"%1.0lf \t\t" \
		GPRINT:dSNR:AVERAGE:"%1.0lf \t\t" \
		GPRINT:dSNR:MAX:"%1.0lf \n" \
		\
		LINE1:uLineScaled#7648EC:"uLine\t\t\t" \
		GPRINT:uLine:LAST:"%1.0lf \t\t" \
		GPRINT:uLine:MIN:"%1.0lf \t\t" \
		GPRINT:uLine:AVERAGE:"%1.0lf \t\t" \
		GPRINT:uLine:MAX:"%1.0lf \n" \
		\
		LINE1:dLineScaled#4D18E4:"dLine\t\t\t" \
		GPRINT:dLine:LAST:"%1.0lf \t\t" \
		GPRINT:dLine:MIN:"%1.0lf \t\t" \
		GPRINT:dLine:AVERAGE:"%1.0lf \t\t" \
		GPRINT:dLine:MAX:"%1.0lf \n"
}

# Graph LTE values
#
# $1 - filenme
# $2 - title
# $3 - starttime
# $4 - endtime
function drawLTE {
	rm -f $OUTPUTDIR/$1

	rrdtool graph $OUTPUTDIR/$1 \
		-s $3 \
		-e $4 \
		-t "$2" \
		-h 200 \
		-w 548 \
		-a PNG \
		-v "rsrp" \
		--right-axis 0.1:0 \
		--right-axis-label "rsrq" \
		DEF:rsrq=$LTEDB:rsrq:AVERAGE \
		DEF:rsrp=$LTEDB:rsrp:AVERAGE \
		CDEF:rsrqScaled=rsrq,10,* \
		\
		COMMENT:" \t\t\t" \
		COMMENT:"Cur\: \t\t" \
		COMMENT:"Min\: \t\t" \
		COMMENT:"Avg\: \t\t" \
		COMMENT:"Max\: \n" \
		\
		AREA:rsrp#54EC48CC \
		LINE1:rsrp#24BC14:"rsrp \t\t\t"  \
		GPRINT:rsrp:LAST:"%1.0lf dB\t\t" \
		GPRINT:rsrp:MAX:"%1.0lf dB\t\t" \
		GPRINT:rsrp:AVERAGE:"%1.0lf dB\t\t" \
		GPRINT:rsrp:MIN:"%1.0lf dB\n" \
		\
		AREA:rsrqScaled#48C4EC \
		LINE1:rsrqScaled#1598C3:"rsrq\t\t\t" \
		GPRINT:rsrq:LAST:"%1.0lf dB\t\t" \
		GPRINT:rsrq:MAX:"%1.0lf dB\t\t" \
		GPRINT:rsrq:AVERAGE:"%1.0lf dB\t\t" \
		GPRINT:rsrq:MIN:"%1.0lf dB\n"
}

drawDSL dsl-1h.png "DSL line status - 1 hour" end-1h now &
drawDSL dsl-day.png "DSL line status - by day" end-24h now &
drawDSL dsl-week.png "DSL line status - by week" end-168h now &

drawLTE lteinfo-1h.png "LTE status - 1 hour" end-1h now &
drawLTE lteinfo-day.png "LTE status - by day" end-24h now &
drawLTE lteinfo-week.png "LTE status - by week" end-168h now &

for job in `jobs -p`
do
    wait $job 
done
