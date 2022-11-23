pipeline {
  agent none
  stages {
    stage("deploy") {
      stages {
        stage("staging") {
          when {
            beforeAgent true
            anyOf {
              branch 'staging'
              changelog '.*^\\[ci staging\\] .+$'
            }
          }
          agent { label 'staging' }
          steps {
            sh "./install.sh staging"
          }
        }

        stage ("production"){
          when {
            beforeAgent true
            expression { false }
          }
          agent { label 'production' }
          steps {
            sh "./install.sh production"
          }
        }
      }
    }
  }
}
