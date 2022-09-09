import argparse
import os
import csv
import operator

parser =  argparse.ArgumentParser()
parser.add_argument('file', type=str, help='Path to fenix exported file.')
parser.add_argument('group_column_name', type=str, help='Name of the column containing the groups of each student.')

args = parser.parse_args()
index = 0
with open(args.file, "r") as exportedFile:
    txt = exportedFile.read()
    for record in txt.split("\n"):
        line = record.split(";")
        for i in range(len(line)):
            if line[i] == args.group_column_name:
                index = i
                break

    data = csv.reader(open(args.file),delimiter=';')
    data = sorted(data, key=operator.itemgetter(index))





