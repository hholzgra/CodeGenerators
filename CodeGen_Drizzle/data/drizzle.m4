dnl
dnl configure.in helper macros
dnl 
 
dnl TODO: fix "mutual exclusive" stuff

DRIZZLE_VERSION=none

dnl check for a --with-drizzle configure option and set up
dnl DRIZZLE_CONFIG and MYSLQ_VERSION variables for further use
dnl this must always be called before any other macro from this file
dnl
dnl WITH_DRIZZLE()
dnl
AC_DEFUN([WITH_DRIZZLE], [ 
  AC_MSG_CHECKING(for drizzle_config executable)

  # try to find the drizzle_config script,
  # --with-drizzle will either accept its path directly
  # or will treat it as the drizzle install prefix and will 
  # search for the script in there
  # if no path is given at all we look for the script in
  # /usr/bin and /usr/local/drizzle/bin
  AC_ARG_WITH(drizzle, [  --with-drizzle=PATH       path to drizzle_config binary or drizzle prefix dir], [
    if test $withval = "no"
    then
      DRIZZLE_CONFIG="no"
    else
      if test -x $withval -a -f $withval
      then
        DRIZZLE_CONFIG=$withval
        DRIZZLE_PREFIX=`dirname \`dirname $withval\``
      elif test -x $withval/bin/drizzle_config -a -f $withval/bin/drizzle_config
      then 
        DRIZZLE_CONFIG=$withval/bin/drizzle_config
        DRIZZLE_PREFIX=$withval
      fi
    fi
  ], [
    # implicit "yes", check in $PATH and in known default prefix, 
    # but only if source not already configured
    if test "x$DRIZZLE_SRCDIR" != "x"
    then
      DRIZZLE_CONFIG="no"
    elif DRIZZLE_CONFIG=`which drizzle_config` 
    then      
      DRIZZLE_PREFIX=`dirname \`dirname $DRIZZLE_CONFIG\``
    elif test -x /usr/local/drizzle/bin/drizzle_config -a -f /usr/local/drizzle/bin/drizzle_config 
    then
      DRIZZLE_CONFIG=/usr/local/drizzle/bin/drizzle_config
      DRIZZLE_PREFIX=/usr/local/drizzle
    fi
  ])

  if test "x$DRIZZLE_CONFIG" = "x" 
  then
    AC_MSG_ERROR([not found])
  elif test "$DRIZZLE_CONFIG" = "no" 
  then
    DRIZZLE_CONFIG=""
    DRIZZLE_PREFIX=""
    AC_MSG_RESULT([no])
  else
    if test "x$DRIZZLE_SRCDIR" != "x"
    then
      AC_MSG_ERROR("--with-drizzle can't be used together with --with-drizzle-src")
    else
      # get installed version
      DRIZZLE_VERSION=`$DRIZZLE_CONFIG --version`

      DRIZZLE_CONFIG_INCLUDE=`$DRIZZLE_CONFIG --include`
      DRIZZLE_CONFIG_LIBS_R=`$DRIZZLE_CONFIG --libs_r`

      DRIZZLE_CLIENT=`dirname $DRIZZLE_CONFIG`/drizzle

      AC_MSG_RESULT($DRIZZLE_CONFIG)
    fi
  fi
])



dnl check for a --with-drizzle-src configure option and set up
dnl DRIZZLE_CONFIG and MYSLQ_VERSION variables for further use
dnl this must always be called before any other macro from this file
dnl
dnl if you use this together with WITH_DRIZZLE you have to put this in front of it
dnl
dnl WITH_DRIZZLE_SRC()
dnl
AC_DEFUN([WITH_DRIZZLE_SRC], [ 
  AC_MSG_CHECKING(for drizzle source directory)

  AC_ARG_WITH(drizzle-src, [  --with-drizzle-src=PATH   path to drizzle sourcecode], [
    if test "x$DRIZZLE_CONFIG" != "x"
    then
      AC_MSG_ERROR([--with-drizzle-src can't be used together with --with-drizzle])
    fi

    if test -f $withval/include/drizzle_version.h.in
    then
        if test -f $withval/include/drizzle_version.h
        then
            AC_MSG_RESULT(ok)
            DRIZZLE_SRCDIR=$withval
            DRIZZLE_VERSION=`grep DRIZZLE_SERVER_VERSION $DRIZZLE_SRCDIR/include/drizzle_version.h | sed -e's/"$//g' -e's/.*"//g'`
        else
            AC_MSG_ERROR([not configured yet])
        fi
    else
        AC_MSG_ERROR([$withval doesn't look like a drizzle source dir])
    fi
  ], [
        AC_MSG_RESULT(no)
  ])

  if test "x$DRIZZLE_SRCDIR" != "x"
  then
    DRIZZLE_CONFIG_INCLUDE="-I$DRIZZLE_SRCDIR/include -I$DRIZZLE_SRCDIR  -I$DRIZZLE_SRCDIR/sql -I$DRIZZLE_SRCDIR/regex"
    DRIZZLE_CONFIG_LIBS_R="-L$DRIZZLE_SRCDIR/libdrizzle_r/.libs -ldrizzleclient_r -lz -lm"
  fi
])


dnl
dnl check for successfull drizzle detection
dnl and register AC_SUBST variables
dnl
dnl DRIZZLE_SUBST()
dnl
AC_DEFUN([DRIZZLE_SUBST], [
  if test "$DRIZZLE_VERSION" = "none" 
  then
    AC_MSG_ERROR([Drizzle required but not found])
  fi
   
  # register replacement vars, these will be filled
  # with contant by the other macros 
  AC_SUBST([DRIZZLE_CFLAGS])
  AC_SUBST([DRIZZLE_CXXFLAGS])
  AC_SUBST([DRIZZLE_LDFLAGS])
  AC_SUBST([DRIZZLE_LIBS])
  AC_SUBST([DRIZZLE_VERSION])
])


dnl check if current Drizzle version meets a version requirement
dnl and act accordingly
dnl
dnl DRIZZLE_CHECK_VERSION([requested_version],[yes_action],[no_action])
dnl 
AC_DEFUN([DRIZZLE_CHECK_VERSION], [
  AX_COMPARE_VERSION([$DRIZZLE_VERSION], [GE], [$1], [$2], [$3])
])



dnl check if current Drizzle version meets a version requirement
dnl and bail out with an error message if not
dnl
dnl DRIZZLE_NEED_VERSION([need_version])
dnl 
AC_DEFUN([DRIZZLE_NEED_VERSION], [
  AC_MSG_CHECKING([drizzle version >= $1])
  DRIZZLE_CHECK_VERSION([$1], 
    [AC_MSG_RESULT([yes ($DRIZZLE_VERSION)])], 
    [AC_MSG_ERROR([no ($DRIZZLE_VERSION)])])
])



dnl check whether the installed server was compiled with libdbug
dnl
dnl TODO we also need to figure out whether we need to define
dnl SAFEMALLOC, maybe PEDANTIC_SAFEMALLOC and SAFE_MUTEX, too
dnl else we may run into errors like 
dnl
dnl   Can't open shared library '...' 
dnl   (errno: 22 undefined symbol: my_no_flags_free)
dnl
dnl on loading plugins
dnl
dnl DRIZZLE_DEBUG_SERVER()
dnl
AC_DEFUN([DRIZZLE_DEBUG_SERVER], [
  AC_MSG_CHECKING(for drizzled debug version)

  DRIZZLE_DBUG=unknown

  OLD_CFLAGS=$CFLAGS
  CFLAGS="$CFLAGS $DRIZZLE_CONFIG_INCLUDE"
  OLD_CXXFLAGS=$CXXFLAGS
  CXXFLAGS="$CXXFLAGS $DRIZZLE_CONFIG_INCLUDE"
  # check for DBUG_ON/OFF being defined in my_config.h
  AC_TRY_COMPILE(,[
#include "my_config.h"
#ifdef DBUG_ON
  int ok;
#else
#  ifdef DBUG_OFF
  int ok;
#  else
  choke me
#  endif
#endif
  ],AS_VAR_SET(DRIZZLE_DBUG, ["defined by header file"]),AS_VAR_SET(DRIZZLE_DBUG, unknown))
  CFLAGS=$OLD_CFLAGS
  CXXFLAGS=$OLD_CXXFLAGS


  if test "$DRIZZLE_DBUG" = "unknown"
  then
    # fallback: need to check drizzled binary itself
    # check $prefix/libexec, $prefix/sbin, $prefix/bin in that order
    for dir in libexec sbin bin
    do
      DRIZZLED=$DRIZZLE_PREFIX/$dir/drizzled
      if test -f $DRIZZLED -a -x $DRIZZLED
      then
        if ($DRIZZLED --help --verbose | grep -q -- "--debug")
        then
          AC_DEFINE([DBUG_ON], [1], [Use libdbug])
          DRIZZLE_DBUG=yes
        else
          AC_DEFINE([DBUG_OFF], [1], [Don't use libdbug])
          DRIZZLE_DBUG=no
        fi
        break;
      fi
    done
  fi

  if test "$DRIZZLE_DBUG" = "unknown"
  then
    # still unknown? make sure not to use it then
    AC_DEFINE([DBUG_OFF], [1], [Don't use libdbug])
    DRIZZLE_DBUG="unknown, assuming no"
  fi

  AC_MSG_RESULT($DRIZZLE_DBUG)
  # 
])



dnl set up variables for compilation of regular C API applications
dnl with optional embedded server
dnl 
dnl DRIZZLE_USE_CLIENT_API()
dnl
AC_DEFUN([DRIZZLE_USE_CLIENT_API], [
  # add regular Drizzle C flags
  ADDFLAGS=$DRIZZLE_CONFIG_INCLUDE 

  DRIZZLE_CFLAGS="$DRIZZLE_CFLAGS $ADDFLAGS"    
  DRIZZLE_CXXFLAGS="$DRIZZLE_CXXFLAGS $ADDFLAGS"    

  # add linker flags for client lib
  AC_ARG_ENABLE([embedded-drizzle], [  --enable-embedded-drizzle enable the Drizzle embedded server feature], 
    [DRIZZLE_EMBEDDED_LDFLAGS()],
    [DRIZZLE_LDFLAGS="$DRIZZLE_LDFLAGS $DRIZZLE_CONFIG_LIBS_R"])
])



dnl set up variables for compilation of regular C API applications
dnl with mandatory embedded server
dnl 
dnl DRIZZLE_USE_EMBEDDED_API()
dnl
AC_DEFUN([DRIZZLE_USE_EMBEDDED_API], [
  # add regular Drizzle C flags
  ADDFLAGS=$DRIZZLE_CONFIG_INCLUDE 

  DRIZZLE_CFLAGS="$DRIZZLE_CFLAGS $ADDFLAGS"    
  DRIZZLE_CXXFLAGS="$DRIZZLE_CXXFLAGS $ADDFLAGS"    

  DRIZZLE_EMBEDDED_LDFLAGS()
])


dnl
AC_DEFUN([DRIZZLE_EMBEDDED_LDFLAGS], [
  DRIZZLE_LDFLAGS="$DRIZZLE_LDFLAGS "`$DRIZZLE_CONFIG --libdrizzled-libs`

  AC_MSG_CHECKING([for missing libs])
  OLD_CFLAGS=$CFLAGS
  OLD_LIBS=$LIBS
  CFLAGS="$CFLAGS $DRIZZLE_CFLAGS"
  for MISSING_LIBS in " " "-lz" "-lssl" "-lz -lssl"
  do
    LIBS="$OLD_LIBS $DRIZZLE_LDFLAGS $MISSING_LIBS"
    AC_TRY_LINK([
#include <stdio.h>
#include <drizzle.h>
    ],[ 
      drizzle_server_init(0, NULL, NULL);
    ], [
      LINK_OK=yes
    ], [
      LINK_OK=no
    ])
    if test $LINK_OK = "yes"
    then
      DRIZZLE_LDFLAGS="$DRIZZLE_LDFLAGS $MISSING_LIBS"
      AC_MSG_RESULT([$MISSING_LIBS])
      break;
    fi
  done
  if test $LINK_OK = "no"
  then
    AC_MSG_ERROR([linking still fails])
  fi

  LIBS=$OLD_LIBS
  CFLAGS=$OLD_CFLAGS
])




dnl set up variables for compilation of NDBAPI applications
dnl 
dnl DRIZZLE_USE_NDB_API()
dnl
AC_DEFUN([DRIZZLE_USE_NDB_API], [
  DRIZZLE_USE_CLIENT_API()
  AC_PROG_CXX
  DRIZZLE_CHECK_VERSION([5.0.0],[  

    # drizzle_config results need some post processing for now

    # the include pathes changed in 5.1.x due
    # to the pluggable storage engine clenups,
    # it also dependes on whether we build against
    # drizzle source or installed headers
    if test "x$DRIZZLE_SRCDIR" = "x"
    then 
      IBASE=$DRIZZLE_CONFIG_INCLUDE
    else
      IBASE=$DRIZZLE_SRCDIR
    fi
    DRIZZLE_CHECK_VERSION([5.1.0], [
      IBASE="$IBASE/storage/ndb"
    ],[
      IBASE="$IBASE/ndb"
    ])
    if test "x$DRIZZLE_SRCDIR" != "x"
    then 
      IBASE="$DRIZZLE_SRCDIR/include"
    fi

    # add the ndbapi specifc include dirs
    ADDFLAGS="$ADDFLAGS $IBASE"
    ADDFLAGS="$ADDFLAGS $IBASE/ndbapi"
    ADDFLAGS="$ADDFLAGS $IBASE/mgmapi"

    DRIZZLE_CFLAGS="$DRIZZLE_CFLAGS $ADDFLAGS"
    DRIZZLE_CXXFLAGS="$DRIZZLE_CXXFLAGS $ADDFLAGS"

    # check for ndbapi header file NdbApi.hpp
    AC_LANG_PUSH(C++)
    OLD_CXXFLAGS=$CXXFLAGS
    CXXFLAGS="$CXXFLAGS $DRIZZLE_CXXFLAGS"
    AC_CHECK_HEADER([NdbApi.hpp],,[AC_ERROR(["Can't find NdbApi header files"])])
    CXXFLAGS=$OLD_CXXFLAGS
    AC_LANG_POP()

    # check for the ndbapi client library
    AC_LANG_PUSH(C++)
    OLD_LIBS=$LIBS
    LIBS="$LIBS $DRIZZLE_LIBS -lmysys -lmystrings"
    OLD_LDFLAGS=$LDFLAGS
    LDFLAGS="$LDFLAGS $DRIZZLE_LDFLAGS"
    AC_CHECK_LIB([ndbclient],[ndb_init],,[AC_ERROR(["Can't find NdbApi client lib"])]) 
    LIBS=$OLD_LIBS
    LDFLAGS=$OLD_LDFLAGS
    AC_LANG_POP()

    # add the ndbapi specific static libs
    DRIZZLE_LIBS="$DRIZZLE_LIBS -lndbclient -lmysys -lmystrings "    

  ],[
    AC_ERROR(["NdbApi needs at lest Drizzle 5.0"])
  ])
])



dnl set up variables for compilation of UDF extensions
dnl 
dnl DRIZZLE_USE_UDF_API()
dnl
AC_DEFUN([DRIZZLE_USE_UDF_API], [
  # add regular Drizzle C flags
  ADDFLAGS=$DRIZZLE_CONFIG_INCLUDE 

  DRIZZLE_CFLAGS="$DRIZZLE_CFLAGS $ADDFLAGS"    
  DRIZZLE_CXXFLAGS="$DRIZZLE_CXXFLAGS $ADDFLAGS"    

  DRIZZLE_DEBUG_SERVER()
])



dnl set up variables for compilation of plugins
dnl 
dnl DRIZZLE_USE_PLUGIN_API()
dnl
AC_DEFUN([DRIZZLE_USE_PLUGIN_API], [
  # for plugins the recommended way to include plugin.h 
  # is <drizzle/plugin.h>, not <plugin.h>, so we have to
  # strip the trailing /drizzle from the include path
  # reported by drizzle_config
  ADDFLAGS=`echo $DRIZZLE_CONFIG_INCLUDE | sed -e"s/\/drizzle\$//g"` 

  DRIZZLE_CFLAGS="$ADDFLAGS $DRIZZLE_CONFIG_INCLUDE $DRIZZLE_CFLAGS -DDRIZZLE_DYNAMIC_PLUGIN"    

  DRIZZLE_CXXFLAGS="$ADDFLAGS $DRIZZLE_CONFIG_INCLUDE $DRIZZLE_CXXFLAGS -DDRIZZLE_DYNAMIC_PLUGIN"
  DRIZZLE_CXXFLAGS="$DRIZZLE_CXXFLAGS -fno-implicit-templates -fno-exceptions -fno-rtti"    

  DRIZZLE_DEBUG_SERVER()
])


