import pandas as pd
import json
import argparse
import csv



def csvtojson(args):

    column_names = ["category", "sub_category","sub_category_type", "count"]
    df = pd.read_csv(args.q, header = None, names = column_names)
    json_name = args.q.rstrip("vsc") + "json"
    jsonfile = open(json_name , 'w')
    # print(df)
    # choose columns to keep, in the desired nested json hierarchical order

    df = df[["category", "sub_category","sub_category_type", "count"]]

    # order in the groupby here matters, it determines the json nesting
    # the groupby call makes a pandas series by grouping "category", "sub_category" and"sub_category_type", 
    #while summing the numerical column 'count'
    df1 = df.groupby(["category", "sub_category","sub_category_type"])['count'].sum()
    df1 = df1.reset_index()

    # print (df1)

    d = dict()
    d = {"name":"ARG", "children": []}

    for line in df1.values:
        category = line[0]
        sub_category = line[1]
        sub_category_type = line[2]
        count = line[3]
        # print(category,sub_category,sub_category_type,count)
        # make a list of keys
        category_list = []
        for item in d['children']:
            category_list.append(item['name'])

        # if 'category' is NOT category_list, append it
        if not category in category_list:
            d['children'].append({"name":category, "children":[{"name":sub_category, "children":[{"name": sub_category_type, "count" : count}]}]})

        # if 'category' IS in category_list, add a new child to it
        else:
            sub_list = []        
            for item in d['children'][category_list.index(category)]['children']:
                sub_list.append(item['name'])
            # print sub_list

            if not sub_category in sub_list:
                d['children'][category_list.index(category)]['children'].append({"name":sub_category, "children":[{"name": sub_category_type, "count" : count}]})
            else:
                d['children'][category_list.index(category)]['children'][sub_list.index(sub_category)]['children'].append({"name": sub_category_type, "count" : count})
    # for row in d:
        # print(row)
    # print(json.dumps(d))
    # json.dumps(d,jsonfile)
    jsonfile.write(json.dumps(d))

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Csv to Json')
    parser.add_argument('-q',required=True,help='Csv file')

   
    args=parser.parse_args()
    csvtojson(args)