This assumes that you are running a Unix-y shell, and you have:

* the AWS CLI tools installed
* suitable AWS configuration to use the tools to talk to the Acas AWS estate
* environment variables set for the account you're working with
  * ACAS_ACCOUNT - the AWS account number
  * ACAS_ENV – the Acas environment (one of `pre-prod` or `prod`)

You can get the AWS account number running something like this:

```sh
aws sts get-caller-identity --output text --query 'Account' --profile acas-pre-prod
```

## Validation

You can do basic validation locally to ensure your change won't fail due to
invalid formatting.

```sh
aws cloudformation validate-template \
    --template-body file://$(pwd)/application/monitoring_stack.template \
    --profile "acas-${ACAS_ENV}"
```

## Packaging

You will need to package up your changes so that they are ready
for deployment.

```sh
aws cloudformation package --template-file "$(pwd)/application/monitoring_stack.template" \
    --s3-bucket "acas-cfn-${ACAS_ACCOUNT}-eu-west-1" --s3-prefix "advice/$ACAS_ENV" \
    --output-template-file "$(pwd)/application/advice-monitoring-${ACAS_ENV}.packaged" \
    --profile "acas-${ACAS_ENV}"
```

## Create change set

A change set is a logical operation that AWS will try to execute which will
take the infrastructure from the current state to the declared desired state.
We use change sets since this is the safest way of introducing a feedback loop
and checking that our code will have the intended effect.

```sh
aws cloudformation create-change-set \
    --change-set-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --stack-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --template-body  "file://$(pwd)/application/advice-monitoring-${ACAS_ENV}.packaged" \
    --capabilities CAPABILITY_NAMED_IAM \
    --parameters "file://$(pwd)/application/parameters/${ACAS_ENV}.json" \
    --profile "acas-${ACAS_ENV}"
```

You might find these commands useful to get the parameter values:

### EC2 instance ID
```sh
aws ec2 describe-instances --profile "acas-${ACAS_ENV}" | jq ".Reservations|.[]|.Instances|.[]|.InstanceId"
```

### RDS Cluster name
```sh
aws rds describe-db-clusters --profile "acas-${ACAS_ENV}" | jq ".DBClusters|.[]|.DBClusterIdentifier"
```

### SNS Application Stack

```sh
aws cloudformation describe-stacks --profile "acas-${ACAS_ENV}" | jq ".Stacks|.[]|.StackName"
```

## View change set
Once you've created your change set, you can then view the change set (or look
at it in the AWS web console):

```sh
aws cloudformation describe-change-set \
    --change-set-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --stack-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --profile "acas-${ACAS_ENV}"
```
## Execute change set

If you wish to proceed, then execute the change set.

```sh
aws cloudformation execute-change-set \
    --change-set-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --stack-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --profile "acas-${ACAS_ENV}"
```

## Delete change set

Alternatively, delete the change set and try again.

```sh
aws cloudformation delete-change-set \
    --change-set-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --stack-name "ACAS-advice-monitoring-${ACAS_ENV}" \
    --profile "acas-${ACAS_ENV}"
```
